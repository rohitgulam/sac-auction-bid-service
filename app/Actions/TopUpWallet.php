<?php

namespace App\Actions;

use App\Models\Dealer;
use App\Models\Wallet;
use App\Models\WalletTopUp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TopUpWallet
{
    public const ALLOWED_AMOUNTS_AED = [10_000, 50_000];

    public function handle(Dealer $dealer, int $amountMinor, string $idempotencyKey): TopUpResult
    {
        $result = DB::transaction(function () use ($dealer, $amountMinor, $idempotencyKey): TopUpResult {
            $wallet = Wallet::query()->where('dealer_id', $dealer->id)->lockForUpdate()->firstOrFail();
            $existing = WalletTopUp::query()
                ->where('wallet_id', $wallet->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                if ($existing->amount_minor !== $amountMinor) {
                    return new TopUpResult(409, [
                        'error' => ['code' => 'idempotency_key_reused', 'message' => 'Use a new Idempotency-Key for a different top-up amount.'],
                    ]);
                }

                return new TopUpResult(201, $existing->response_body);
            }

            $wallet->deposit_minor += $amountMinor;
            $wallet->save();

            $topUp = WalletTopUp::query()->create([
                'wallet_id' => $wallet->id,
                'idempotency_key' => $idempotencyKey,
                'amount_minor' => $amountMinor,
            ]);
            $body = [
                'wallet' => $wallet->summary(),
                'activity' => $topUp->activity(),
            ];
            $topUp->update(['response_body' => $body]);

            return new TopUpResult(201, $body);
        }, 3);

        Log::info('Demo wallet top-up processed', [
            'dealer_id' => $dealer->id,
            'outcome' => $result->body['error']['code'] ?? 'credited',
        ]);

        return $result;
    }
}
