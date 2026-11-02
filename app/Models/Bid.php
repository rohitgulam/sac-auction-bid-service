<?php

namespace App\Models;

use Database\Factories\BidFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['auction_listing_id', 'dealer_id', 'amount_minor'])]
class Bid extends Model
{
    /** @use HasFactory<BidFactory> */
    use HasFactory;

    public function listing(): BelongsTo
    {
        return $this->belongsTo(AuctionListing::class, 'auction_listing_id');
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }
}
