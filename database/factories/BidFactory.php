<?php

namespace Database\Factories;

use App\Models\AuctionListing;
use App\Models\Bid;
use App\Models\Dealer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bid>
 */
class BidFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'auction_listing_id' => AuctionListing::factory(),
            'dealer_id' => Dealer::factory(),
            'amount_minor' => 2_100_000,
        ];
    }
}
