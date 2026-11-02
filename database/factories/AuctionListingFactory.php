<?php

namespace Database\Factories;

use App\Models\AuctionListing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuctionListing>
 */
class AuctionListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lot_number' => 'CAR-'.$this->faker->unique()->numerify('#####'),
            'kind' => 'vehicle',
            'offer_type' => 'live',
            'title' => 'Toyota Corolla',
            'subtitle' => 'Fictional inspected demo lot',
            'photo_key' => 'corolla',
            'location' => 'North Yard',
            'opening_amount_minor' => 2_000_000,
            'vehicle_year' => 2023,
            'mileage_km' => 34_000,
            'inspection_summary' => [
                'body' => 'Light cosmetic wear',
                'interior' => 'Clean cabin',
                'mechanical' => 'Starts and drives',
            ],
            'plate_number' => null,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMinutes(11),
        ];
    }

    public function plate(): static
    {
        return $this->state(fn (): array => [
            'lot_number' => 'PLT-'.$this->faker->unique()->numerify('#####'),
            'kind' => 'plate',
            'title' => 'Fictional plate',
            'subtitle' => 'Display code for demonstration only',
            'vehicle_year' => null,
            'mileage_km' => null,
            'inspection_summary' => null,
            'plate_number' => 'DEMO '.$this->faker->unique()->numerify('####'),
            'photo_key' => null,
        ]);
    }

    public function deal(): static
    {
        return $this->state(fn (): array => [
            'offer_type' => 'deal',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(71),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
        ]);
    }
}
