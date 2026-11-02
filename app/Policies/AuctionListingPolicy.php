<?php

namespace App\Policies;

use App\Models\AuctionListing;
use App\Models\Dealer;

class AuctionListingPolicy
{
    public function placeBid(Dealer $dealer, AuctionListing $listing): bool
    {
        return $dealer->exists && $listing->exists && $dealer->wallet()->exists();
    }
}
