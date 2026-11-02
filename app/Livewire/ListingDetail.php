<?php

namespace App\Livewire;

use App\Models\AuctionListing;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListingDetail extends Component
{
    public int $listingId;

    public function mount(AuctionListing $listing): void
    {
        $this->listingId = $listing->id;
    }

    public function render(): View
    {
        return view('livewire.listing-detail', [
            'listing' => AuctionListing::query()->with('bids.dealer')->findOrFail($this->listingId),
        ]);
    }
}
