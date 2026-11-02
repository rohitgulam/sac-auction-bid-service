<?php

namespace App\Actions;

use App\Models\AuctionListing;
use App\Models\Bid;
use App\Models\BidReservation;
use App\Models\Dealer;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaceBid
{
    public function handle(Dealer $dealer, AuctionListing $listing, int $amountMinor, string $idempotencyKey): BidResult
    {
        $result = DB::transaction(function () use ($dealer, $listing, $amountMinor, $idempotencyKey): BidResult {
            $lockedListing = AuctionListing::query()->whereKey($listing->id)->lockForUpdate()->firstOrFail();
            $existingAttempt = DB::table('bid_attempts')
                ->where('auction_listing_id', $lockedListing->id)
                ->where('dealer_id', $dealer->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingAttempt) {
                if ((int) $existingAttempt->amount_minor !== $amountMinor) {
                    return new BidResult(409, [
                        'error' => ['code' => 'idempotency_key_reused', 'message' => 'Use a new Idempotency-Key for a different bid amount.'],
                    ]);
                }

                return new BidResult((int) $existingAttempt->response_status, json_decode($existingAttempt->response_body, true, flags: JSON_THROW_ON_ERROR));
            }

            if (! $lockedListing->isOpen()) {
                return $this->record($lockedListing, $dealer, $amountMinor, $idempotencyKey, $this->rejected('listing_closed', 'Bidding is closed for this listing.'));
            }

            $highBid = $lockedListing->highBid();
            $minimumMinor = ($highBid?->amount_minor ?? $lockedListing->opening_amount_minor) + 100;

            if ($amountMinor < $minimumMinor) {
                return $this->record($lockedListing, $dealer, $amountMinor, $idempotencyKey, $this->rejected('bid_too_low', 'Bid at least AED 1 above the current high bid or opening amount.'));
            }

            $wallets = Wallet::query()
                ->whereIn('dealer_id', array_unique([$dealer->id, $highBid?->dealer_id]))
                ->orderBy('dealer_id')
                ->lockForUpdate()
                ->get();
            $wallet = $wallets->firstWhere('dealer_id', $dealer->id);

            if (! $wallet) {
                return $this->record($lockedListing, $dealer, $amountMinor, $idempotencyKey, $this->rejected('wallet_missing', 'This demo dealer has no wallet.'));
            }

            $reservedMinor = (int) BidReservation::query()
                ->where('dealer_id', $dealer->id)
                ->whereNull('released_at')
                ->sum('amount_minor');
            $priorReservation = $highBid
                ? BidReservation::query()->where('bid_id', $highBid->id)->whereNull('released_at')->first()
                : null;
            $replacedMinor = $highBid?->dealer_id === $dealer->id ? ($priorReservation?->amount_minor ?? 0) : 0;

            if ($reservedMinor - $replacedMinor + $amountMinor > $wallet->deposit_minor) {
                return $this->record($lockedListing, $dealer, $amountMinor, $idempotencyKey, $this->rejected('limit_exceeded', 'This bid exceeds your available deposit-backed limit.'));
            }

            $bid = Bid::query()->create([
                'auction_listing_id' => $lockedListing->id,
                'dealer_id' => $dealer->id,
                'amount_minor' => $amountMinor,
            ]);

            if ($priorReservation) {
                $priorReservation->update(['released_at' => now()]);
            }

            BidReservation::query()->create([
                'bid_id' => $bid->id,
                'auction_listing_id' => $lockedListing->id,
                'dealer_id' => $dealer->id,
                'amount_minor' => $amountMinor,
            ]);

            $body = [
                'bid' => [
                    'id' => $bid->id,
                    'bidder_code' => $dealer->code,
                    'amount_aed' => intdiv($amountMinor, 100),
                    'currency' => 'AED',
                ],
                'wallet' => $wallet->summary(),
            ];

            return $this->record($lockedListing, $dealer, $amountMinor, $idempotencyKey, new BidResult(201, $body));
        }, 3);

        Log::info('Demo bid processed', [
            'listing_id' => $listing->id,
            'dealer_id' => $dealer->id,
            'outcome' => $result->body['error']['code'] ?? 'accepted',
        ]);

        return $result;
    }

    private function rejected(string $code, string $message): BidResult
    {
        return new BidResult(409, ['error' => ['code' => $code, 'message' => $message]]);
    }

    private function record(AuctionListing $listing, Dealer $dealer, int $amountMinor, string $idempotencyKey, BidResult $result): BidResult
    {
        DB::table('bid_attempts')->insert([
            'auction_listing_id' => $listing->id,
            'dealer_id' => $dealer->id,
            'idempotency_key' => $idempotencyKey,
            'amount_minor' => $amountMinor,
            'response_status' => $result->status,
            'response_body' => json_encode($result->body, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $result;
    }
}
