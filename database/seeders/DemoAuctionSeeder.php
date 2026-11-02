<?php

namespace Database\Seeders;

use App\Models\AuctionListing;
use App\Models\Bid;
use App\Models\BidReservation;
use App\Models\Dealer;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoAuctionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            if (AuctionListing::query()->exists()) {
                return;
            }

            $dealers = collect(range(1, 20))->map(function (int $number): Dealer {
                $dealer = Dealer::factory()->create(['code' => sprintf('D-%03d', $number)]);
                Wallet::factory()->create(['dealer_id' => $dealer->id]);

                return $dealer;
            });

            $vehiclePhotos = collect(config('auction_photos'));

            for ($number = 1; $number <= 40; $number++) {
                $offerType = $number <= 16 ? 'live' : 'deal';
                $isClosed = $number > 32;
                $photoKey = $vehiclePhotos->keys()[($number - 1) % $vehiclePhotos->count()];
                $listing = AuctionListing::factory()->create([
                    'lot_number' => sprintf('CAR-%03d', $number),
                    'offer_type' => $offerType,
                    'title' => $vehiclePhotos[$photoKey]['model'],
                    'subtitle' => sprintf('%d model · inspected demo lot', 2020 + ($number % 6)),
                    'photo_key' => $photoKey,
                    'location' => ['North Yard', 'Harbor Point', 'Central Depot'][$number % 3],
                    'opening_amount_minor' => (18_000 + $number * 750) * 100,
                    'vehicle_year' => 2020 + ($number % 6),
                    'mileage_km' => 12_000 + $number * 1_900,
                    'inspection_summary' => [
                        'body' => ['Light cosmetic wear', 'Clean exterior', 'Minor scuffs noted'][$number % 3],
                        'interior' => ['Clean cabin', 'Normal seat wear', 'Well-kept trim'][$number % 3],
                        'mechanical' => 'Starts and drives in demo inspection',
                    ],
                    ...$this->offerWindow($offerType, $isClosed),
                ]);

                if ($number % 3 === 0) {
                    $this->seedBidHistory($listing, $dealers[($number * 2) % 20], $dealers[($number * 2 + 1) % 20]);
                }
            }

            for ($number = 1; $number <= 10; $number++) {
                $offerType = $number <= 4 ? 'live' : 'deal';
                $isClosed = $number > 8;
                $listing = AuctionListing::factory()->plate()->create([
                    'lot_number' => sprintf('PLT-%03d', $number),
                    'offer_type' => $offerType,
                    'title' => sprintf('Demo plate %02d', $number),
                    'subtitle' => 'Fictional display plate',
                    'location' => 'Demo Registry',
                    'plate_number' => sprintf('DEMO %04d', 2400 + $number * 13),
                    'opening_amount_minor' => (5_000 + $number * 500) * 100,
                    ...$this->offerWindow($offerType, $isClosed),
                ]);

                if ($number % 3 === 0) {
                    $this->seedBidHistory($listing, $dealers[($number * 2) % 20], $dealers[($number * 2 + 1) % 20]);
                }
            }
        });
    }

    /**
     * @return array{starts_at: Carbon, ends_at: Carbon}
     */
    private function offerWindow(string $offerType, bool $isClosed): array
    {
        $now = now();

        if ($isClosed) {
            $endsAt = $now->copy()->subHour();

            return [
                'starts_at' => $offerType === 'live'
                    ? $endsAt->copy()->subMinutes(12)
                    : $endsAt->copy()->subHours(72),
                'ends_at' => $endsAt,
            ];
        }

        return $offerType === 'live'
            ? ['starts_at' => $now->copy()->subMinute(), 'ends_at' => $now->copy()->addMinutes(11)]
            : ['starts_at' => $now->copy()->subHour(), 'ends_at' => $now->copy()->addHours(71)];
    }

    private function seedBidHistory(AuctionListing $listing, Dealer $firstDealer, Dealer $secondDealer): void
    {
        $firstBidTime = $listing->starts_at->copy()->addSeconds(20);
        $secondBidTime = $listing->starts_at->copy()->addSeconds(40);

        $firstBid = Bid::factory()->create([
            'auction_listing_id' => $listing->id,
            'dealer_id' => $firstDealer->id,
            'amount_minor' => $listing->opening_amount_minor + 100_000,
            'created_at' => $firstBidTime,
            'updated_at' => $firstBidTime,
        ]);
        BidReservation::factory()->forBid($firstBid)->create([
            'released_at' => $secondBidTime,
            'created_at' => $firstBidTime,
            'updated_at' => $secondBidTime,
        ]);

        $secondBid = Bid::factory()->create([
            'auction_listing_id' => $listing->id,
            'dealer_id' => $secondDealer->id,
            'amount_minor' => $listing->opening_amount_minor + 200_000,
            'created_at' => $secondBidTime,
            'updated_at' => $secondBidTime,
        ]);
        BidReservation::factory()->forBid($secondBid)->create([
            'created_at' => $secondBidTime,
            'updated_at' => $secondBidTime,
        ]);
    }
}
