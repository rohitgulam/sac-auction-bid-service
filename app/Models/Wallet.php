<?php

namespace App\Models;

use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dealer_id', 'deposit_minor'])]
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory;

    /**
     * @return array{deposit_aed: int, reserved_aed: int, available_aed: int, currency: string}
     */
    public function summary(): array
    {
        $reservedMinor = (int) BidReservation::query()->where('dealer_id', $this->dealer_id)->whereNull('released_at')->sum('amount_minor');

        return [
            'deposit_aed' => intdiv($this->deposit_minor, 100),
            'reserved_aed' => intdiv($reservedMinor, 100),
            'available_aed' => intdiv($this->deposit_minor - $reservedMinor, 100),
            'currency' => 'AED',
        ];
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }
}
