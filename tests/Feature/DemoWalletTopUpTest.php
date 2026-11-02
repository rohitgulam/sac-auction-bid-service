<?php

namespace Tests\Feature;

use App\Livewire\DemoWallet;
use App\Livewire\WalletSummary;
use App\Models\AuctionListing;
use App\Models\Bid;
use App\Models\BidReservation;
use App\Models\Dealer;
use App\Models\Wallet;
use App\Models\WalletTopUp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DemoWalletTopUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_dealer_can_add_a_simulated_fixed_top_up(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 2_000_000]);

        $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', 'top-up-1')
            ->postJson(route('api.v1.wallet.top-ups.store'), ['amount_aed' => 10_000])
            ->assertCreated()
            ->assertJsonPath('wallet.deposit_aed', 30_000)
            ->assertJsonPath('wallet.available_aed', 30_000)
            ->assertJsonPath('activity.amount_aed', 10_000)
            ->assertJsonStructure(['activity' => ['id', 'type', 'amount_aed', 'created_at']]);

        $this->getJson(route('api.v1.wallet.show'))
            ->assertOk()
            ->assertJsonPath('activity.0.amount_aed', 10_000);
        $this->assertSame(1, WalletTopUp::query()->count());
    }

    public function test_top_up_increases_available_limit_without_changing_existing_reservation_or_bid(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 3_000_000]);
        $listing = AuctionListing::factory()->create();

        $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', 'bid-before-top-up')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 21_000])
            ->assertCreated();

        $this->withHeader('Idempotency-Key', 'top-up-after-bid')
            ->postJson(route('api.v1.wallet.top-ups.store'), ['amount_aed' => 10_000])
            ->assertCreated()
            ->assertJsonPath('wallet.deposit_aed', 40_000)
            ->assertJsonPath('wallet.reserved_aed', 21_000)
            ->assertJsonPath('wallet.available_aed', 19_000);

        $this->assertSame(1, Bid::query()->count());
        $this->assertSame(1, BidReservation::query()->whereNull('released_at')->count());
        $this->getJson(route('api.v1.listings.bids.index', $listing))->assertJsonCount(1, 'data');
    }

    public function test_repeating_top_up_key_credits_once_and_returns_original_result(): void
    {
        $dealer = Dealer::factory()->create();
        $wallet = Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 2_000_000]);
        $url = route('api.v1.wallet.top-ups.store');

        $original = $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', 'first-top-up')
            ->postJson($url, ['amount_aed' => 50_000])
            ->assertCreated()
            ->json();

        $this->withHeader('Idempotency-Key', 'second-top-up')
            ->postJson($url, ['amount_aed' => 10_000])
            ->assertCreated()
            ->assertJsonPath('wallet.deposit_aed', 80_000);
        $this->withHeader('Idempotency-Key', 'first-top-up')
            ->postJson($url, ['amount_aed' => 50_000])
            ->assertCreated()
            ->assertExactJson($original);
        $this->withHeader('Idempotency-Key', 'first-top-up')
            ->postJson($url, ['amount_aed' => 10_000])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'idempotency_key_reused');

        $this->assertSame(8_000_000, $wallet->fresh()->deposit_minor);
        $this->assertSame(2, WalletTopUp::query()->count());
        $this->getJson(route('api.v1.wallet.show'))->assertJsonCount(2, 'activity');
    }

    public function test_top_up_requires_a_selected_dealer_and_one_of_the_two_fixed_amounts(): void
    {
        $dealer = Dealer::factory()->create();
        $wallet = Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 2_000_000]);
        $url = route('api.v1.wallet.top-ups.store');

        $this->withHeader('Idempotency-Key', 'guest')
            ->postJson($url, ['amount_aed' => 10_000, 'dealer_id' => $dealer->id])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'demo_dealer_required');
        $this->getJson(route('api.v1.wallet.show'))->assertUnauthorized();

        $this->withSession(['demo_dealer_id' => $dealer->id])
            ->withHeader('Idempotency-Key', '')
            ->postJson($url, ['amount_aed' => 10_000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idempotency_key');
        foreach ([5_000, 10_000.5, 0] as $amount) {
            $this->withHeader('Idempotency-Key', 'invalid-'.$amount)
                ->postJson($url, ['amount_aed' => $amount])
                ->assertUnprocessable()
                ->assertJsonPath('error.code', 'invalid_top_up')
                ->assertJsonValidationErrors('amount_aed');
        }

        $this->assertSame(2_000_000, $wallet->fresh()->deposit_minor);
        $this->assertSame(0, WalletTopUp::query()->count());
    }

    public function test_dealer_can_simulate_top_up_and_review_activity_in_livewire_wallet(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 2_000_000]);
        $this->withSession(['demo_dealer_id' => $dealer->id]);

        Livewire::test(DemoWallet::class)
            ->assertSee('Simulate a deposit top-up')
            ->assertSee('No payment or personal details are collected')
            ->call('topUp', 50_000)
            ->assertSee('AED 70,000')
            ->assertSee('Simulated AED 50,000 top-up added')
            ->assertSee('Wallet activity')
            ->assertSee('+ AED 50,000');

        $this->getJson(route('api.v1.wallet.show'))
            ->assertJsonPath('deposit_aed', 70_000)
            ->assertJsonPath('activity.0.amount_aed', 50_000);
    }

    public function test_livewire_wallet_cannot_top_up_without_a_selected_dealer(): void
    {
        Livewire::test(DemoWallet::class)
            ->assertDontSee('Simulate a deposit top-up')
            ->call('topUp', 10_000)
            ->assertDontSee('AED 10,000');

        $this->assertSame(0, WalletTopUp::query()->count());
    }

    public function test_livewire_wallet_rejects_invalid_amount_and_does_not_double_credit_same_key(): void
    {
        $dealer = Dealer::factory()->create();
        $wallet = Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 2_000_000]);
        $this->withSession(['demo_dealer_id' => $dealer->id]);

        $component = Livewire::test(DemoWallet::class);
        $key = $component->get('idempotencyKey');
        $component->call('topUp', 5_000)->assertSee('Choose AED 10,000 or AED 50,000');
        $component->call('topUp', 10_000)->assertSee('AED 30,000');
        $component->set('idempotencyKey', $key)->call('topUp', 10_000)->assertSee('AED 30,000');

        $this->assertSame(3_000_000, $wallet->fresh()->deposit_minor);
        $this->assertSame(1, WalletTopUp::query()->count());
    }

    public function test_new_bid_key_can_succeed_after_top_up_while_old_rejection_remains_stable(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 2_000_000]);
        $listing = AuctionListing::factory()->create();
        $bidUrl = route('api.v1.listings.bids.store', $listing);
        $this->withSession(['demo_dealer_id' => $dealer->id]);

        $this->withHeader('Idempotency-Key', 'rejected-bid')
            ->postJson($bidUrl, ['amount_aed' => 21_000])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'limit_exceeded');
        $this->withHeader('Idempotency-Key', 'top-up')
            ->postJson(route('api.v1.wallet.top-ups.store'), ['amount_aed' => 10_000])
            ->assertCreated();
        $this->withHeader('Idempotency-Key', 'rejected-bid')
            ->postJson($bidUrl, ['amount_aed' => 21_000])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'limit_exceeded');
        $this->withHeader('Idempotency-Key', 'new-bid')
            ->postJson($bidUrl, ['amount_aed' => 21_000])
            ->assertCreated()
            ->assertJsonPath('wallet.available_aed', 9_000);

        $this->assertSame(1, Bid::query()->count());
        $this->assertSame(1, BidReservation::query()->whereNull('released_at')->count());
    }

    public function test_top_up_controls_live_only_on_wallet_management_page(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id]);
        $listing = AuctionListing::factory()->create();
        $this->withSession(['demo_dealer_id' => $dealer->id]);

        $this->get(route('dashboard'))->assertOk()->assertSee('Manage wallet')->assertDontSee('Simulate a deposit top-up');
        $this->get(route('listings.show', $listing))->assertOk()->assertSee('Manage wallet')->assertDontSee('Simulate a deposit top-up');
        $this->get(route('wallet.manage'))->assertOk()->assertSee('Simulate a deposit top-up');
    }

    public function test_wallet_component_refreshes_after_a_bid_changes_reservations(): void
    {
        $dealer = Dealer::factory()->create();
        Wallet::factory()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 3_000_000]);
        $listing = AuctionListing::factory()->create();
        $this->withSession(['demo_dealer_id' => $dealer->id]);

        $component = Livewire::test(WalletSummary::class)->assertSee('AED 30,000');
        $this->withHeader('Idempotency-Key', 'bid-for-refresh')
            ->postJson(route('api.v1.listings.bids.store', $listing), ['amount_aed' => 21_000])
            ->assertCreated();
        $component->dispatch('wallet-changed')->assertSee('AED 9,000');
    }
}
