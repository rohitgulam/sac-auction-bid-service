<?php

namespace App\Livewire;

use App\Models\AuctionListing;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AuctionDashboard extends Component
{
    public string $kind = 'all';

    public string $offer = 'all';

    public string $status = 'all';

    public function render(): View
    {
        $listings = AuctionListing::query()
            ->with('bids.dealer')
            ->when($this->kind !== 'all', fn ($query) => $query->where('kind', $this->kind))
            ->when($this->offer !== 'all', fn ($query) => $query->where('offer_type', $this->offer))
            ->when($this->status === 'open', fn ($query) => $query->open())
            ->when($this->status === 'closed', fn ($query) => $query->closed())
            ->orderByRaw('CASE WHEN ends_at > NOW() THEN 0 ELSE 1 END')
            ->orderBy('ends_at')
            ->get();

        return view('livewire.auction-dashboard', [
            'listings' => $listings,
            'totalListings' => AuctionListing::query()->count(),
            'openListings' => AuctionListing::query()->open()->count(),
        ]);
    }
}
