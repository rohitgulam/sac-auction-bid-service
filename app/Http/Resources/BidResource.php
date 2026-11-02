<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BidResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bidder_code' => $this->dealer->code,
            'amount_aed' => intdiv($this->amount_minor, 100),
            'currency' => 'AED',
            'placed_at' => $this->created_at->toIso8601String(),
        ];
    }
}
