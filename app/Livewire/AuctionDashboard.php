<?php

namespace App\Livewire;

use App\Actions\EnsureOpenOffers;
use App\Actions\SelectDemoDealer;
use App\Models\AuctionListing;
use App\Models\Dealer;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AuctionDashboard extends Component
{
    public string $kind = 'all';

    public string $offer = 'all';

    public string $status = 'all';

    public function mount(EnsureOpenOffers $ensureOpenOffers, SelectDemoDealer $selectDemoDealer): void
    {
        $ensureOpenOffers->handle();

        if (session()->has('demo_dealer_id')) {
            return;
        }

        $defaultDealer = Dealer::query()->where('code', 'D-001')->whereHas('wallet')->first()
            ?? Dealer::query()->whereHas('wallet')->orderBy('code')->first();

        if ($defaultDealer) {
            $selectDemoDealer->handle($defaultDealer);
        }
    }

    public function render(): View
    {
        $listings = AuctionListing::query()
            ->select(['id', 'lot_number', 'kind', 'offer_type', 'title', 'subtitle', 'location', 'opening_amount_minor', 'photo_key', 'plate_number', 'starts_at', 'ends_at'])
            ->withCount('bids')
            ->withMax('bids as latest_bid_at', 'created_at')
            ->with('leadingBid.dealer')
            ->when($this->kind !== 'all', fn ($query) => $query->where('kind', $this->kind))
            ->when($this->offer !== 'all', fn ($query) => $query->where('offer_type', $this->offer))
            ->when($this->status === 'open', fn ($query) => $query->open())
            ->when($this->status === 'closed', fn ($query) => $query->closed())
            ->orderByRaw('CASE WHEN ends_at > NOW() THEN 0 ELSE 1 END')
            ->orderByRaw('latest_bid_at DESC NULLS LAST')
            ->orderBy('ends_at')
            ->get();

        return view('livewire.auction-dashboard', [
            'listings' => $listings,
            'totalListings' => AuctionListing::query()->count(),
            'openListings' => AuctionListing::query()->open()->count(),
        ]);
    }
}
