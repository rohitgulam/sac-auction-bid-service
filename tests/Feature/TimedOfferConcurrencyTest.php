<?php

namespace Tests\Feature;

use App\Models\AuctionListing;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TimedOfferConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_simultaneous_dashboard_visits_create_only_one_offer_per_type(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('This PostgreSQL concurrency test requires pcntl.');
        }

        DB::unprepared('CREATE OR REPLACE FUNCTION pause_demo_offer_insert() RETURNS trigger AS $$ BEGIN PERFORM pg_sleep(0.3); RETURN NEW; END; $$ LANGUAGE plpgsql');
        DB::unprepared('CREATE TRIGGER pause_demo_offer_insert BEFORE INSERT ON auction_listings FOR EACH ROW EXECUTE FUNCTION pause_demo_offer_insert()');

        try {
            $workers = [];

            for ($attempt = 0; $attempt < 2; $attempt++) {
                $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
                $this->assertNotFalse($sockets);
                $processId = pcntl_fork();
                $this->assertNotSame(-1, $processId);

                if ($processId === 0) {
                    fclose($sockets[0]);
                    fread($sockets[1], 1);
                    fclose($sockets[1]);
                    DB::purge('pgsql');

                    try {
                        $this->get(route('dashboard'))->assertOk();
                        exit(0);
                    } catch (\Throwable $exception) {
                        fwrite(STDERR, (string) $exception);
                        exit(1);
                    }
                }

                fclose($sockets[1]);
                $workers[] = ['pid' => $processId, 'socket' => $sockets[0]];
            }

            foreach ($workers as $worker) {
                fwrite($worker['socket'], 'go');
                fclose($worker['socket']);
            }

            foreach ($workers as $worker) {
                pcntl_waitpid($worker['pid'], $status);
                $this->assertTrue(pcntl_wifexited($status));
                $this->assertSame(0, pcntl_wexitstatus($status));
            }

            $this->assertSame(2, AuctionListing::query()->count());
            $this->assertSame(1, AuctionListing::query()->where('offer_type', 'live')->open()->count());
            $this->assertSame(1, AuctionListing::query()->where('offer_type', 'deal')->open()->count());
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS pause_demo_offer_insert ON auction_listings');
            DB::unprepared('DROP FUNCTION IF EXISTS pause_demo_offer_insert()');
        }
    }
}
