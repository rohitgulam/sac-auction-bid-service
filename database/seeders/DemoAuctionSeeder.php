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
            $dealerNames = [
                'Harborline Motors', 'Crescent Auto House', 'Blue Dune Trading', 'Northgate Vehicle Co',
                'Palm Avenue Motors', 'Cedar Drive Autos', 'Marina Road Motors', 'Desert Bridge Auto',
                'Atlas Lane Motors', 'Pearl Coast Trading', 'Summit Wheel House', 'Quartz Motor Group',
                'Willow Park Auto', 'Ember Road Motors', 'Meridian Vehicle Co', 'Coastline Auto House',
                'Copper Gate Motors', 'Skyline Motor Yard', 'Dunecrest Auto', 'Silver Harbor Motors',
            ];

            if (AuctionListing::query()->exists()) {
                foreach ($dealerNames as $index => $name) {
                    Dealer::query()->where('code', sprintf('D-%03d', $index + 1))->update(['name' => $name]);
                }

                return;
            }

            $dealers = collect(range(1, 20))->map(function (int $number) use ($dealerNames): Dealer {
                $dealer = Dealer::query()->create([
                    'code' => sprintf('D-%03d', $number),
                    'name' => $dealerNames[$number - 1],
                ]);
                Wallet::query()->create(['dealer_id' => $dealer->id, 'deposit_minor' => 100_000_000]);

                return $dealer;
            });

            $vehiclePhotos = collect(config('auction_photos'));

            for ($number = 1; $number <= 40; $number++) {
                $offerType = $number <= 16 ? 'live' : 'deal';
                $isClosed = $number > 32;
                $photoKey = $vehiclePhotos->keys()[($number - 1) % $vehiclePhotos->count()];
                $listing = AuctionListing::query()->create([
                    'lot_number' => sprintf('CAR-%03d', $number),
                    'kind' => 'vehicle',
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
                $listing = AuctionListing::query()->create([
                    'lot_number' => sprintf('PLT-%03d', $number),
                    'kind' => 'plate',
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

        $firstBid = new Bid([
            'auction_listing_id' => $listing->id,
            'dealer_id' => $firstDealer->id,
            'amount_minor' => $listing->opening_amount_minor + 100_000,
        ]);
        $firstBid->created_at = $firstBidTime;
        $firstBid->updated_at = $firstBidTime;
        $firstBid->save();

        $firstReservation = new BidReservation([
            'bid_id' => $firstBid->id,
            'auction_listing_id' => $listing->id,
            'dealer_id' => $firstDealer->id,
            'amount_minor' => $firstBid->amount_minor,
            'released_at' => $secondBidTime,
        ]);
        $firstReservation->created_at = $firstBidTime;
        $firstReservation->updated_at = $secondBidTime;
        $firstReservation->save();

        $secondBid = new Bid([
            'auction_listing_id' => $listing->id,
            'dealer_id' => $secondDealer->id,
            'amount_minor' => $listing->opening_amount_minor + 200_000,
        ]);
        $secondBid->created_at = $secondBidTime;
        $secondBid->updated_at = $secondBidTime;
        $secondBid->save();

        $secondReservation = new BidReservation([
            'bid_id' => $secondBid->id,
            'auction_listing_id' => $listing->id,
            'dealer_id' => $secondDealer->id,
            'amount_minor' => $secondBid->amount_minor,
        ]);
        $secondReservation->created_at = $secondBidTime;
        $secondReservation->updated_at = $secondBidTime;
        $secondReservation->save();
    }
}
