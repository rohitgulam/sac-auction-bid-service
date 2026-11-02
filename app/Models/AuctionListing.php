<?php

namespace App\Models;

use Database\Factories\AuctionListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['lot_number', 'kind', 'offer_type', 'title', 'subtitle', 'location', 'opening_amount_minor', 'vehicle_year', 'mileage_km', 'inspection_summary', 'plate_number', 'photo_key', 'starts_at', 'ends_at'])]
class AuctionListing extends Model
{
    /** @use HasFactory<AuctionListingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inspection_summary' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('ends_at', '<=', now());
    }

    public function isOpen(): bool
    {
        return $this->starts_at->isPast() && $this->ends_at->isFuture();
    }

    public function highBid(): ?Bid
    {
        if ($this->relationLoaded('bids')) {
            return $this->bids->sortByDesc('amount_minor')->first();
        }

        return $this->bids()->orderByDesc('amount_minor')->orderByDesc('id')->first();
    }

    public function photoUrl(): ?string
    {
        if ($this->kind !== 'vehicle' || ! $this->photo_key || ! config('auction_photos.'.$this->photo_key)) {
            return null;
        }

        $r2PublicUrl = config('filesystems.disks.r2.url');

        return $r2PublicUrl
            ? rtrim($r2PublicUrl, '/').'/vehicles/'.$this->photo_key.'.jpg'
            : asset('images/vehicles/'.$this->photo_key.'.jpg');
    }

    /**
     * @return array{model: string, photographer: string, source: string, license: string, license_url: string}|null
     */
    public function photoCredit(): ?array
    {
        return $this->photo_key ? config('auction_photos.'.$this->photo_key) : null;
    }
}
