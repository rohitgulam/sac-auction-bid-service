<?php

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\WalletTopUp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WalletTopUp>
 */
class WalletTopUpFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'idempotency_key' => (string) Str::uuid(),
            'amount_minor' => 1_000_000,
            'response_body' => [],
        ];
    }
}
