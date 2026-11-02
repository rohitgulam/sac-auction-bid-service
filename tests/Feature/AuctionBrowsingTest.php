<?php

namespace Tests\Feature;

use App\Livewire\AuctionDashboard;
use App\Livewire\DemoDealerChooser;
use App\Models\AuctionListing;
use App\Models\BidReservation;
use App\Models\Dealer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuctionBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_listings_are_publicly_browsable(): void
    {
        $this->seed();

        $this->getJson('/api/v1/listings')
            ->assertOk()
            ->assertJsonCount(50, 'data')
            ->assertJsonPath('data.0.currency', 'AED');
    }

    public function test_visitor_can_browse_the_auction_without_selecting_a_dealer(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertSee('Aster Nova')
            ->assertSee('Independent portfolio demonstration using fictional data.');
    }

    public function test_seeded_inventory_has_vehicle_plate_and_offer_states(): void
    {
        $this->seed();

        $this->getJson('/api/v1/listings?kind=vehicle')->assertOk()->assertJsonCount(40, 'data');
        $this->getJson('/api/v1/listings?kind=plate')->assertOk()->assertJsonCount(10, 'data');
        $this->getJson('/api/v1/listings?status=open')->assertOk()->assertJsonCount(40, 'data');
        $this->getJson('/api/v1/listings?status=closed')->assertOk()->assertJsonCount(10, 'data');
        $this->assertSame(20, Dealer::query()->count());
    }

    public function test_live_offer_status_matches_its_open_window(): void
    {
        $this->seed();
        $listing = AuctionListing::query()->where('offer_type', 'live')->firstOrFail();

        $this->getJson('/api/v1/listings/'.$listing->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'open');
    }

    public function test_seeded_offer_windows_have_the_agreed_duration(): void
    {
        $this->seed();

        foreach (AuctionListing::all() as $listing) {
            $expectedMinutes = $listing->offer_type === 'live' ? 12 : 72 * 60;

            $this->assertSame($expectedMinutes, (int) $listing->starts_at->diffInMinutes($listing->ends_at));
        }
    }

    public function test_visitor_can_inspect_vehicle_plate_and_code_only_bid_history(): void
    {
        $this->seed();
        $vehicle = AuctionListing::query()->where('kind', 'vehicle')->whereHas('bids')->firstOrFail();
        $plate = AuctionListing::query()->where('kind', 'plate')->firstOrFail();

        $this->get(route('listings.show', $vehicle))
            ->assertOk()
            ->assertSee('Inspection summary')
            ->assertSee('Bid history')
            ->assertSee('D-');

        $this->get(route('listings.show', $plate))
            ->assertOk()
            ->assertSee('Plate details')
            ->assertSee($plate->plate_number);

        $this->getJson('/api/v1/listings/'.$vehicle->id.'/bids')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['bidder_code', 'amount_aed', 'currency']]]);
    }

    public function test_visitor_can_filter_the_auction_floor_to_plates(): void
    {
        $this->seed();

        Livewire::test(AuctionDashboard::class)
            ->set('kind', 'plate')
            ->assertSee('Demo plate 01')
            ->assertDontSee('Aster Nova');
    }

    public function test_seeded_high_bids_have_one_active_reservation_each(): void
    {
        $this->seed();

        foreach (AuctionListing::query()->whereHas('bids')->with('bids')->get() as $listing) {
            $highBid = $listing->highBid();
            $activeReservations = BidReservation::query()
                ->where('auction_listing_id', $listing->id)
                ->whereNull('released_at')
                ->get();

            $this->assertCount(1, $activeReservations);
            $this->assertSame($highBid->id, $activeReservations->first()->bid_id);
            $this->assertSame($highBid->amount_minor, $activeReservations->first()->amount_minor);
            $this->assertTrue($listing->bids->every(fn ($bid) => $bid->created_at->lte($listing->ends_at)));
        }
    }

    public function test_visitor_can_choose_a_demo_dealer_by_code(): void
    {
        $this->seed();

        Livewire::test(DemoDealerChooser::class)
            ->set('code', 'D-014')
            ->call('selectDealer')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->getJson('/api/v1/demo/dashboard')
            ->assertOk()
            ->assertJsonPath('selected_bidder_code', 'D-014');
    }

    public function test_invalid_dealer_code_cannot_be_selected(): void
    {
        $this->seed();

        Livewire::test(DemoDealerChooser::class)
            ->set('code', 'UNKNOWN')
            ->call('selectDealer')
            ->assertHasErrors(['code']);

        $this->getJson('/api/v1/demo/dashboard')
            ->assertJsonPath('selected_bidder_code', null);
    }

    public function test_demo_dealer_identity_comes_from_each_browser_session(): void
    {
        $this->seed();
        $firstDealer = Dealer::query()->where('code', 'D-001')->firstOrFail();
        $secondDealer = Dealer::query()->where('code', 'D-020')->firstOrFail();

        $this->withSession(['demo_dealer_id' => $firstDealer->id, 'demo_dealer_code' => $firstDealer->code])
            ->getJson('/api/v1/demo/dashboard')
            ->assertJsonPath('selected_bidder_code', 'D-001');

        $this->withSession(['demo_dealer_id' => $secondDealer->id, 'demo_dealer_code' => $secondDealer->code])
            ->getJson('/api/v1/demo/dashboard')
            ->assertJsonPath('selected_bidder_code', 'D-020');
    }
}
