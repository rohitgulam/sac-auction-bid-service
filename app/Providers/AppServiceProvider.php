<?php

namespace App\Providers;

use App\Models\AuctionListing;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('bids', function (Request $request): Limit|Unlimited {
            $dealerId = $request->session()->get('demo_dealer_id');
            $listing = $request->route('listing');
            $listingId = $listing instanceof AuctionListing
                ? $listing->id
                : (is_string($listing) && ctype_digit($listing) ? (int) $listing : null);
            $idempotencyKey = $request->header('Idempotency-Key');

            if ($dealerId && $listingId && is_string($idempotencyKey)
                && DB::table('bid_attempts')
                    ->where('dealer_id', $dealerId)
                    ->where('auction_listing_id', $listingId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->exists()) {
                return Limit::none();
            }

            return Limit::perMinute(20)
                ->by((string) ($dealerId ?: $request->ip()))
                ->response(fn (): JsonResponse => response()->json([
                    'error' => ['code' => 'bid_rate_limited', 'message' => 'Wait a moment before submitting another bid.'],
                ], 429));
        });
    }
}
