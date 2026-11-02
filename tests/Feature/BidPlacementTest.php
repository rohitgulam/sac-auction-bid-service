<?php

namespace Tests\Feature;

use App\Livewire\ListingDetail;
use App\Models\AuctionListing;
use App\Models\Bid;
use App\Models\BidReservation;
use App\Models\Dealer;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BidPlacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_bid_reserves_its_amount_and_reduces_available_limit(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 3_000_000]);
        $listing = AuctionListing::factory()->create(['opening_amount_minor' => 2_000_000]);

        $this->withSession(['demo_dealer_id' => $dealer->id, 'demo_dealer_code' => $dealer->code])
            ->withHeader('Idempotency-Key', 'first-bid')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 21_000])
            ->assertCreated()
            ->assertJsonPath('bid.amount_aed', 21_000)
            ->assertJsonPath('wallet.reserved_aed', 21_000)
            ->assertJsonPath('wallet.available_aed', 9_000);

        $this->assertSame(1, Bid::query()->count());
        $this->assertSame(1, BidReservation::query()->whereNull('released_at')->count());
    }

    public function test_competing_bid_releases_the_displaced_dealers_reservation_and_keeps_history(): void
    {
        $first = Dealer::factory()->create();
        $second = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $first->id, 'deposit_minor' => 3_000_000]);
        Wallet::factory()->create(['dealer_id' => $second->id, 'deposit_minor' => 3_000_000]);
        $listing = AuctionListing::factory()->create();

        $this->withSession(['demo_dealer_id' => $first->id])
            ->withHeader('Idempotency-Key', 'first')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 21_000])
            ->assertCreated();

        $this->withSession(['demo_dealer_id' => $second->id])
            ->withHeader('Idempotency-Key', 'second')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 22_000])
            ->assertCreated()
            ->assertJsonPath('wallet.available_aed', 8_000);

        $this->getJson(route('api.v1.wallet.show'))->assertJsonPath('reserved_aed', 22_000);
        $this->withSession(['demo_dealer_id' => $first->id])
            ->getJson(route('api.v1.wallet.show'))
            ->assertJsonPath('available_aed', 30_000);
        $this->getJson(route('api.v1.listings.bids.index', $listing))
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.bidder_code', $second->code);
        $this->assertSame(1, BidReservation::query()->whereNull('released_at')->count());
    }

    public function test_own_higher_bid_replaces_reservation_using_only_incremental_capacity(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 2_200_000]);
        $listing = AuctionListing::factory()->create();

        $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', 'original')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 21_000])
            ->assertCreated();

        $this->withHeader('Idempotency-Key', 'replacement')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 22_000])
            ->assertCreated()
            ->assertJsonPath('wallet.reserved_aed', 22_000)
            ->assertJsonPath('wallet.available_aed', 0);

        $this->assertSame(2, Bid::query()->count());
        $this->assertSame(1, BidReservation::query()->whereNull('released_at')->count());
    }

    public function test_over_capacity_bid_is_rejected_without_bid_or_reservation(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 2_000_000]);
        $listing = AuctionListing::factory()->create();

        $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', 'over-capacity')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 21_000])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'limit_exceeded');

        $this->assertSame(0, Bid::query()->count());
        $this->assertSame(0, BidReservation::query()->count());
        $this->getJson(route('api.v1.wallet.show'))->assertJsonPath('available_aed', 20_000);
    }

    public function test_bid_at_or_below_high_bid_is_rejected_without_side_effects(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 3_000_000]);
        $listing = AuctionListing::factory()->create();

        $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', 'first')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 21_000])
            ->assertCreated();

        foreach ([21_000, 20_000] as $amount) {
            $this->withHeader('Idempotency-Key', 'low-'.$amount)
                ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => $amount])
                ->assertStatus(409)
                ->assertJsonPath('error.code', 'bid_too_low');
        }

        $this->assertSame(1, Bid::query()->count());
        $this->assertSame(1, BidReservation::query()->whereNull('released_at')->count());
        $this->getJson(route('api.v1.wallet.show'))->assertJsonPath('available_aed', 9_000);
    }

    public function test_closed_listing_rejects_new_bids_and_keeps_winners_reservation(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 3_000_000]);
        $listing = AuctionListing::factory()->create(['ends_at' => now()->addMinute()]);

        $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', 'before-close')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 21_000])
            ->assertCreated();

        $this->travel(2)->minutes();

        $this->withHeader('Idempotency-Key', 'after-close')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 22_000])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'listing_closed');

        $this->getJson(route('api.v1.wallet.show'))->assertJsonPath('reserved_aed', 21_000);
        $this->assertSame(1, Bid::query()->count());
        $this->assertSame(1, BidReservation::query()->whereNull('released_at')->count());
    }

    public function test_accepted_bid_retry_returns_original_result_and_changed_amount_conflicts(): void
    {
        $dealer = Dealer::factory()->create();
        $wallet = Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 3_000_000]);
        $listing = AuctionListing::factory()->create();
        $url = route('api.v1.listings.bids.store', $listing);

        $original = $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', 'retry-me')
            ->postJson($url, ['amount_aed' => 21_000])
            ->assertCreated()
            ->json();

        $wallet->update(['deposit_minor' => 4_000_000]);

        $this->withHeader('Idempotency-Key', 'retry-me')
            ->postJson($url, ['amount_aed' => 21_000])
            ->assertCreated()
            ->assertExactJson($original);
        $this->withHeader('Idempotency-Key', 'retry-me')
            ->postJson($url, ['amount_aed' => 22_000])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'idempotency_key_reused');

        $this->assertSame(1, Bid::query()->count());
        $this->assertSame(1, BidReservation::query()->count());
    }

    public function test_rejected_bid_retry_keeps_original_rejection_after_wallet_change(): void
    {
        $dealer = Dealer::factory()->create();
        $wallet = Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 2_000_000]);
        $listing = AuctionListing::factory()->create();
        $url = route('api.v1.listings.bids.store', $listing);

        $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', 'rejected')
            ->postJson($url, ['amount_aed' => 21_000])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'limit_exceeded');

        $wallet->update(['deposit_minor' => 3_000_000]);

        $this->withHeader('Idempotency-Key', 'rejected')
            ->postJson($url, ['amount_aed' => 21_000])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'limit_exceeded');
        $this->assertSame(0, Bid::query()->count());
        $this->assertSame(0, BidReservation::query()->count());
    }

    public function test_bid_requires_selected_dealer_valid_whole_aed_amount_and_idempotency_key(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id]);
        $listing = AuctionListing::factory()->create();
        $url = route('api.v1.listings.bids.store', $listing);

        $this->withHeader('Idempotency-Key', 'guest')
            ->postJson($url, ['amount_aed' => 21_000, 'dealer_id' => $dealer->id])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'demo_dealer_required');
        $this->getJson(route('api.v1.listings.show', $listing))->assertOk();
        $this->getJson(route('api.v1.wallet.show'))->assertUnauthorized();

        $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', '')
            ->postJson($url, ['amount_aed' => 21_000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idempotency_key');
        $this->withHeader('Idempotency-Key', 'fraction')
            ->postJson($url, ['amount_aed' => 21_000.5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount_aed');

        $this->assertSame(0, Bid::query()->count());
        $this->assertSame(0, BidReservation::query()->count());
    }

    public function test_selected_dealer_without_wallet_is_forbidden_from_bidding(): void
    {
        $dealer = Dealer::factory()->create();
        $listing = AuctionListing::factory()->create();

        $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', 'forbidden')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 21_000])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'bid_forbidden');

        $this->assertSame(0, Bid::query()->count());
    }

    public function test_same_key_can_be_used_by_another_dealer_or_on_another_listing(): void
    {
        $first = Dealer::factory()->create();
        $second = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $first->id]);
        Wallet::factory()->create(['dealer_id' => $second->id]);
        $firstListing = AuctionListing::factory()->create();
        $secondListing = AuctionListing::factory()->create();

        $this->withSession(['demo_dealer_id' => $first->id])
            ->withHeader('Idempotency-Key', 'shared')
            ->postJson(route('api.v1.listings.bids.store', $firstListing), ['amount_aed' => 21_000])
            ->assertCreated();
        $this->withHeader('Idempotency-Key', 'shared')
            ->postJson(route('api.v1.listings.bids.store', $secondListing), ['amount_aed' => 21_000])
            ->assertCreated();
        $this->withSession(['demo_dealer_id' => $second->id])
            ->withHeader('Idempotency-Key', 'shared')
            ->postJson(route('api.v1.listings.bids.store', $firstListing), ['amount_aed' => 22_000])
            ->assertCreated();

        $this->assertSame(3, Bid::query()->count());
    }

    public function test_selected_dealer_can_bid_from_listing_page_and_see_wallet_change(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 3_000_000]);
        $listing = AuctionListing::factory()->create();

        $this->withSession(['demo_dealer_id' => $dealer->id, 'demo_dealer_code' => $dealer->code]);

        Livewire::test(ListingDetail::class, ['listing' => $listing])
            ->assertSee('Available to bid')
            ->set('bidAmount', '21,000')
            ->call('submitBid')
            ->assertHasNoErrors()
            ->assertSee('Bid accepted')
            ->assertDispatched('wallet-changed')
            ->assertSee('AED 9,000')
            ->assertSee($dealer->code);

        $this->getJson(route('api.v1.listings.show', $listing))
            ->assertJsonPath('data.high_bid_aed', 21_000);
        $this->getJson(route('api.v1.wallet.show'))
            ->assertJsonPath('available_aed', 9_000);
    }

    public function test_listing_page_shows_a_helpful_limit_error_without_changing_bid_history(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 2_000_000]);
        $listing = AuctionListing::factory()->create();
        $this->withSession(['demo_dealer_id' => $dealer->id]);

        Livewire::test(ListingDetail::class, ['listing' => $listing])
            ->set('bidAmount', '21000')
            ->call('submitBid')
            ->assertSee('exceeds your available deposit-backed limit')
            ->assertSee('No bids yet');

        $this->assertSame(0, Bid::query()->count());
        $this->assertSame(0, BidReservation::query()->count());
    }

    public function test_listing_page_rejects_malformed_thousands_grouping(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id]);
        $listing = AuctionListing::factory()->create();
        $this->withSession(['demo_dealer_id' => $dealer->id]);

        Livewire::test(ListingDetail::class, ['listing' => $listing])
            ->set('bidAmount', '21,00')
            ->call('submitBid')
            ->assertHasErrors(['bidAmount']);

        $this->assertSame(0, Bid::query()->count());
    }

    public function test_active_reservations_on_other_listings_reduce_capacity_for_plate_bids(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 3_000_000]);
        $vehicle = AuctionListing::factory()->create();
        $plate = AuctionListing::factory()->plate()->create(['opening_amount_minor' => 500_000]);

        $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', 'vehicle')
            ->postJson(route('api.v1.listings.bids.store', $vehicle), ['amount_aed' => 21_000])
            ->assertCreated();
        $this->withHeader('Idempotency-Key', 'plate-over')
            ->postJson(route('api.v1.listings.bids.store', $plate), ['amount_aed' => 10_000])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'limit_exceeded');
        $this->withHeader('Idempotency-Key', 'plate-valid')
            ->postJson(route('api.v1.listings.bids.store', $plate), ['amount_aed' => 9_000])
            ->assertCreated()
            ->assertJsonPath('wallet.available_aed', 0);

        $this->assertSame(2, Bid::query()->count());
        $this->assertSame(2, BidReservation::query()->whereNull('released_at')->count());
    }

    public function test_rate_limit_does_not_change_the_result_of_a_stored_idempotent_attempt(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 3_000_000]);
        $listing = AuctionListing::factory()->create();
        $url = route('api.v1.listings.bids.store', $listing);

        $original = $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', 'accepted')
            ->postJson($url, ['amount_aed' => 21_000])
            ->assertCreated()
            ->json();

        for ($attempt = 1; $attempt <= 19; $attempt++) {
            $this->withHeader('Idempotency-Key', 'low-'.$attempt)
                ->postJson($url, ['amount_aed' => 20_000])
                ->assertStatus(409);
        }

        $this->withHeader('Idempotency-Key', 'new-after-limit')
            ->postJson($url, ['amount_aed' => 22_000])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'bid_rate_limited');
        $this->withHeader('Idempotency-Key', 'accepted')
            ->postJson($url, ['amount_aed' => 21_000])
            ->assertCreated()
            ->assertExactJson($original);

        $this->assertSame(1, Bid::query()->count());
    }
}
