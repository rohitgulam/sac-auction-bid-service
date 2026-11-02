<?php

namespace App\Models;

use Database\Factories\WalletTopUpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['wallet_id', 'idempotency_key', 'amount_minor', 'response_body'])]
class WalletTopUp extends Model
{
    /** @use HasFactory<WalletTopUpFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['response_body' => 'array'];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return array{id: int, type: string, amount_aed: int, currency: string, created_at: string}
     */
    public function activity(): array
    {
        return [
            'id' => $this->id,
            'type' => 'demo_top_up',
            'amount_aed' => intdiv($this->amount_minor, 100),
            'currency' => 'AED',
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
