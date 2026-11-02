<?php

namespace App\Http\Resources;

use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuctionListingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Bid|null $highBid */
        $highBid = $this->highBid();

        return [
            'id' => $this->id,
            'lot_number' => $this->lot_number,
            'kind' => $this->kind,
            'offer_type' => $this->offer_type,
            'status' => $this->isOpen() ? 'open' : 'closed',
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'location' => $this->location,
            'currency' => 'AED',
            'opening_amount_aed' => intdiv($this->opening_amount_minor, 100),
            'high_bid_aed' => $highBid ? intdiv($highBid->amount_minor, 100) : null,
            'high_bidder_code' => $highBid?->dealer?->code,
            'bid_count' => $this->bids->count(),
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'vehicle_year' => $this->vehicle_year,
            'mileage_km' => $this->mileage_km,
            'inspection_summary' => $this->inspection_summary,
            'plate_number' => $this->plate_number,
            'photo_url' => $this->photoUrl(),
            'photo_credit' => $this->photoCredit(),
        ];
    }
}
