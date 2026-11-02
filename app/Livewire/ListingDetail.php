<?php

namespace App\Livewire;

use App\Actions\PlaceBid;
use App\Models\AuctionListing;
use App\Models\Dealer;
use App\Policies\AuctionListingPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class ListingDetail extends Component
{
    public int $listingId;

    public string $bidAmount = '';

    public string $idempotencyKey = '';

    public ?string $bidFeedback = null;

    public ?string $bidError = null;

    public function mount(AuctionListing $listing): void
    {
        $this->listingId = $listing->id;
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function submitBid(PlaceBid $placeBid): void
    {
        $dealer = Dealer::query()->find(session('demo_dealer_id'));
        $listing = AuctionListing::query()->findOrFail($this->listingId);
        $this->bidFeedback = null;
        $this->bidError = null;

        if (! $dealer || ! app(AuctionListingPolicy::class)->placeBid($dealer, $listing)) {
            $this->bidError = 'Choose a demo dealer before placing a bid.';

            return;
        }

        $validated = $this->validate([
            'bidAmount' => ['required', 'integer', 'min:1', 'max:1000000000'],
        ]);
        $result = $placeBid->handle($dealer, $listing, (int) $validated['bidAmount'] * 100, $this->idempotencyKey);

        if ($result->status === 201) {
            $this->bidFeedback = 'Bid accepted. You are now leading at AED '.number_format($result->body['bid']['amount_aed']).'.';
            $this->bidAmount = '';
        } else {
            $this->bidError = $result->body['error']['message'];
        }

        $this->idempotencyKey = (string) Str::uuid();
    }

    public function render(): View
    {
        $dealer = Dealer::query()->with('wallet')->find(session('demo_dealer_id'));

        return view('livewire.listing-detail', [
            'listing' => AuctionListing::query()->with('bids.dealer')->findOrFail($this->listingId),
            'selectedDealer' => $dealer,
            'walletSummary' => $dealer?->wallet?->summary(),
        ]);
    }
}
