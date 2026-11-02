<?php

namespace Tests\Feature;

use App\Livewire\AuctionDashboard;
use App\Livewire\DemoWallet;
use App\Livewire\ListingDetail;
use App\Livewire\ProfileMenu;
use App\Models\AuctionListing;
use App\Models\Bid;
use App\Models\BidReservation;
use App\Models\Dealer;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CrossSessionUpdatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_another_dealers_bid_appears_on_the_listing_and_moves_its_card_first(): void
    {
        $viewer = Dealer::factory()->create(['code' => 'D-001']);
        $bidder = Dealer::factory()->create(['code' => 'D-002']);
        Wallet::factory()->create(['dealer_id' => $viewer->id]);
        Wallet::factory()->create(['dealer_id' => $bidder->id]);
        $first = AuctionListing::factory()->create(['title' => 'First lot', 'ends_at' => now()->addHour()]);
        $second = AuctionListing::factory()->create(['title' => 'Second lot', 'ends_at' => now()->addHours(2)]);

        $this->withSession(['demo_dealer_id' => $viewer->id]);
        $detail = Livewire::test(ListingDetail::class, ['listing' => $second])->assertSee('No bids yet')->assertSee('wire:poll.2s', false);
        $dashboard = Livewire::test(AuctionDashboard::class)->assertSeeInOrder(['First lot', 'Second lot'])->assertSee('wire:poll.2s', false);

        $this->withSession(['demo_dealer_id' => $bidder->id])
            ->withHeader('Idempotency-Key', 'external-bid')
            ->postJson(route('api.v1.listings.bids.store', $second), ['amount_aed' => 21_000])
            ->assertCreated();

        $this->withSession(['demo_dealer_id' => $viewer->id]);
        $detail->call('$refresh')->assertSee('AED 21,000')->assertSee('Leading bidder')->assertSee('D-002');
        $dashboard->call('$refresh')->assertSeeInOrder(['Second lot', 'First lot'])->assertSee('Recent bid activity');
        $this->assertSame(1, Bid::query()->count());
        $this->assertSame(1, BidReservation::query()->whereNull('released_at')->count());
    }

    public function test_another_session_top_up_and_outbid_refresh_wallet_and_leader(): void
    {
        $viewer = Dealer::factory()->create(['code' => 'D-001']);
        $bidder = Dealer::factory()->create(['code' => 'D-002']);
        Wallet::factory()->create(['dealer_id' => $viewer->id, 'deposit_minor' => 3_000_000]);
        Wallet::factory()->create(['dealer_id' => $bidder->id, 'deposit_minor' => 3_000_000]);
        $listing = AuctionListing::factory()->create();

        $this->withSession(['demo_dealer_id' => $viewer->id]);
        $detail = Livewire::test(ListingDetail::class, ['listing' => $listing]);
        $wallet = Livewire::test(DemoWallet::class)->assertSee('AED 30,000')->assertSee('wire:poll.2s', false);
        $profile = Livewire::test(ProfileMenu::class)->assertSee('AED 30,000')->assertSee('wire:poll.2s', false);

        $this->withHeader('Idempotency-Key', 'viewers-bid')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 21_000])
            ->assertCreated();
        $detail->call('$refresh')->assertSee('· You')->assertSee('AED 9,000');
        $wallet->call('$refresh')->assertSee('AED 21,000')->assertSee('AED 9,000');

        $this->withSession(['demo_dealer_id' => $bidder->id])
            ->withHeader('Idempotency-Key', 'external-outbid')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 22_000])
            ->assertCreated();
        $this->withSession(['demo_dealer_id' => $viewer->id]);
        $detail->call('$refresh')->assertSee('D-002')->assertSee('AED 30,000');
        $wallet->call('$refresh')->assertSee('AED 0')->assertSee('AED 30,000');

        $this->withHeader('Idempotency-Key', 'external-top-up')
            ->postJson(route('api.v1.wallet.top-ups.store'), ['amount_aed' => 10_000])
            ->assertCreated();
        $wallet->call('$refresh')->assertSee('AED 40,000')->assertSee('+ AED 10,000');
        $profile->call('$refresh')->assertSee('AED 40,000');
        $detail->call('$refresh')->assertSee('AED 40,000');
    }

    public function test_bid_history_shows_dealer_position_changes_without_losing_earlier_bids(): void
    {
        $first = Dealer::factory()->create(['code' => 'D-001']);
        $second = Dealer::factory()->create(['code' => 'D-002']);
        $third = Dealer::factory()->create(['code' => 'D-003']);
        foreach ([$first, $second, $third] as $dealer) {
            Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 10_000_000]);
        }
        $listing = AuctionListing::factory()->create();
        $this->withSession(['demo_dealer_id' => $first->id]);
        $detail = Livewire::test(ListingDetail::class, ['listing' => $listing]);

        foreach ([[$first, 21_000], [$second, 22_000], [$third, 23_000]] as [$dealer, $amount]) {
            $this->withSession(['demo_dealer_id' => $dealer->id])
                ->withHeader('Idempotency-Key', 'bid-'.$amount)
                ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => $amount])
                ->assertCreated();
        }

        $this->withSession(['demo_dealer_id' => $first->id]);
        $detail->call('$refresh')
            ->assertSee('Dealer positions')
            ->assertSee('New leader')
            ->assertSee('Down 1 place')
            ->assertSee('Bid history');

        $this->withHeader('Idempotency-Key', 'returning-bid')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 24_000])
            ->assertCreated();

        $detail->call('$refresh')
            ->assertSee('Up 2 places')
            ->assertSee('Down 1 place')
            ->assertSee('↑')
            ->assertSee('↓')
            ->assertSee('text-green-700', false)
            ->assertSee('text-red-700', false)
            ->assertSeeInOrder(['D-001', 'Up 2 places', 'D-003', 'Down 1 place']);
        $this->getJson(route('api.v1.listings.bids.index', $listing))->assertJsonCount(4, 'data');

        $this->withHeader('Idempotency-Key', 'same-leader-raise')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 25_000])
            ->assertCreated();
        $detail->call('$refresh')->assertDontSee('Up 2 places')->assertDontSee('Down 1 place');
    }
}
