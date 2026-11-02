<?php

namespace Tests\Feature;

use App\Models\AuctionListing;
use App\Models\Bid;
use App\Models\BidReservation;
use App\Models\Dealer;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimedOfferAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_replenishes_an_expired_live_offer_once_without_touching_the_open_deal_offer(): void
    {
        $closedLive = AuctionListing::factory()->closed()->create(['offer_type' => 'live', 'title' => 'Retired live lot']);
        $openDeal = AuctionListing::factory()->deal()->create(['title' => 'Open deal lot']);
        $this->assertTrue($openDeal->isOpen());
        $this->assertSame(2, AuctionListing::query()->count());

        $this->get(route('dashboard'))->assertOk()->assertSee('Retired live lot');
        $this->assertSame(3, AuctionListing::query()->count());
        $this->get(route('dashboard'))->assertOk();

        $freshLive = AuctionListing::query()->where('offer_type', 'live')->open()->sole();
        $this->assertSame(3, AuctionListing::query()->count());
        $this->assertSame(12, (int) $freshLive->starts_at->diffInMinutes($freshLive->ends_at));
        $this->assertSame($openDeal->id, AuctionListing::query()->where('offer_type', 'deal')->open()->sole()->id);
        $this->assertTrue($closedLive->fresh()->ends_at->isPast());
    }

    public function test_dashboard_creates_one_of_each_offer_type_when_inventory_is_empty(): void
    {
        $this->get(route('dashboard'))->assertOk()->assertSee('Demo sedan');
        $this->get(route('dashboard'))->assertOk();

        $live = AuctionListing::query()->where('offer_type', 'live')->open()->sole();
        $deal = AuctionListing::query()->where('offer_type', 'deal')->open()->sole();
        $this->assertSame(2, AuctionListing::query()->count());
        $this->assertSame(12, (int) $live->starts_at->diffInMinutes($live->ends_at));
        $this->assertSame(72, (int) $deal->starts_at->diffInHours($deal->ends_at));
    }

    public function test_last_second_bid_does_not_extend_offer_and_closed_history_survives_replenishment(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 3_000_000]);
        $endsAt = now()->addSeconds(5)->startOfSecond();
        $listing = AuctionListing::factory()->create(['ends_at' => $endsAt]);
        AuctionListing::factory()->deal()->create();
        $this->withSession(['demo_dealer_id' => $dealer->id]);

        $this->travelTo($endsAt->copy()->subSecond());
        $this->withHeader('Idempotency-Key', 'last-second')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 21_000])
            ->assertCreated();
        $this->assertTrue($listing->fresh()->ends_at->equalTo($endsAt));

        $this->travelTo($endsAt);
        $this->withHeader('Idempotency-Key', 'at-close')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 22_000])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'listing_closed');
        $this->get(route('dashboard'))->assertOk();

        $this->assertSame(1, AuctionListing::query()->where('offer_type', 'live')->open()->count());
        $this->getJson(route('api.v1.listings.show', $listing))->assertJsonPath('data.high_bid_aed', 21_000);
        $this->getJson(route('api.v1.listings.bids.index', $listing))->assertJsonCount(1, 'data');
        $this->getJson(route('api.v1.wallet.show'))->assertJsonPath('reserved_aed', 21_000);
        $this->assertSame(1, Bid::query()->count());
        $this->assertSame(1, BidReservation::query()->whereNull('released_at')->count());
    }
}
