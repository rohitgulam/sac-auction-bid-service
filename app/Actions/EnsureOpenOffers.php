<?php

namespace App\Actions;

use App\Models\AuctionListing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnsureOpenOffers
{
    /**
     * @var array<string, array{duration_minutes: int, lock_key: int, lot_prefix: string}>
     */
    private const OFFER_TYPES = [
        'live' => ['duration_minutes' => 12, 'lock_key' => 1_042_001, 'lot_prefix' => 'L-'],
        'deal' => ['duration_minutes' => 72 * 60, 'lock_key' => 1_042_002, 'lot_prefix' => 'D-'],
    ];

    public function handle(): void
    {
        foreach (self::OFFER_TYPES as $offerType => $settings) {
            $createdListing = DB::transaction(function () use ($offerType, $settings): ?AuctionListing {
                DB::select('SELECT pg_advisory_xact_lock(?)', [$settings['lock_key']]);

                if (AuctionListing::query()->where('offer_type', $offerType)->open()->exists()) {
                    return null;
                }

                $template = AuctionListing::query()
                    ->where('offer_type', $offerType)
                    ->where('kind', 'vehicle')
                    ->latest('id')
                    ->first();
                $listing = $template?->replicate() ?? new AuctionListing([
                    'kind' => 'vehicle',
                    'title' => 'Demo sedan',
                    'subtitle' => 'Fictional inspected demo lot',
                    'location' => 'North Yard',
                    'opening_amount_minor' => 2_000_000,
                    'photo_key' => 'corolla',
                    'vehicle_year' => 2023,
                    'mileage_km' => 34_000,
                    'inspection_summary' => [
                        'body' => 'Light cosmetic wear',
                        'interior' => 'Clean cabin',
                        'mechanical' => 'Starts and drives',
                    ],
                ]);
                $startsAt = now();
                $listing->fill([
                    'lot_number' => $settings['lot_prefix'].Str::upper(Str::random(12)),
                    'offer_type' => $offerType,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addMinutes($settings['duration_minutes']),
                ]);
                $listing->save();

                return $listing;
            }, 3);

            if ($createdListing) {
                Log::info('Demo offer replenished', ['listing_id' => $createdListing->id, 'offer_type' => $offerType]);
            }
        }
    }
}
