<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bid_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key', 128);
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedSmallInteger('response_status');
            $table->jsonb('response_body');
            $table->timestamps();

            $table->unique(['auction_listing_id', 'dealer_id', 'idempotency_key'], 'bid_attempt_unique_key');
        });

        DB::statement('CREATE UNIQUE INDEX bid_reservations_one_active_per_listing ON bid_reservations (auction_listing_id) WHERE released_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS bid_reservations_one_active_per_listing');
        Schema::dropIfExists('bid_attempts');
    }
};
