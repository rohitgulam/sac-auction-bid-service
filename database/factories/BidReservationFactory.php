<?php

namespace Database\Factories;

use App\Models\Bid;
use App\Models\BidReservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BidReservation>
 */
class BidReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bid_id' => Bid::factory(),
            'auction_listing_id' => fn (array $attributes): int => Bid::query()->findOrFail($attributes['bid_id'])->auction_listing_id,
            'dealer_id' => fn (array $attributes): int => Bid::query()->findOrFail($attributes['bid_id'])->dealer_id,
            'amount_minor' => fn (array $attributes): int => Bid::query()->findOrFail($attributes['bid_id'])->amount_minor,
            'released_at' => null,
        ];
    }

    public function forBid(Bid $bid): static
    {
        return $this->state(fn (): array => [
            'bid_id' => $bid->id,
            'auction_listing_id' => $bid->auction_listing_id,
            'dealer_id' => $bid->dealer_id,
            'amount_minor' => $bid->amount_minor,
        ]);
    }
}
