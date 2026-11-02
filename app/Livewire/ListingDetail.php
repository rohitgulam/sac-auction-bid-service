<?php

namespace App\Livewire;

use App\Actions\PlaceBid;
use App\Models\AuctionListing;
use App\Models\Bid;
use App\Models\Dealer;
use App\Policies\AuctionListingPolicy;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class ListingDetail extends Component
{
    public int $listingId;

    public string $bidAmount = '';

    public string $idempotencyKey = '';

    public ?string $bidFeedback = null;

    public ?string $bidError = null;

    public function mount(AuctionListing $listing): void
    {
        $this->listingId = $listing->id;
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function submitBid(PlaceBid $placeBid): void
    {
        $dealer = Dealer::query()->find(session('demo_dealer_id'));
        $listing = AuctionListing::query()->findOrFail($this->listingId);
        $this->bidFeedback = null;
        $this->bidError = null;

        if (! $dealer || ! app(AuctionListingPolicy::class)->placeBid($dealer, $listing)) {
            $this->bidError = 'Choose a demo dealer before placing a bid.';

            return;
        }

        $validated = $this->validate([
            'bidAmount' => [
                'required',
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! preg_match('/\A(?:[0-9]+|[1-9][0-9]{0,2}(?:,[0-9]{3})+)\z/', $value)) {
                        $fail('Enter a whole AED amount using digits.');

                        return;
                    }

                    $amountAed = (int) str_replace(',', '', $value);

                    if ($amountAed < 1 || $amountAed > 1_000_000_000) {
                        $fail('Enter an amount between AED 1 and AED 1,000,000,000.');
                    }
                },
            ],
        ]);
        $amountAed = (int) str_replace(',', '', $validated['bidAmount']);
        $result = $placeBid->handle($dealer, $listing, $amountAed * 100, $this->idempotencyKey);

        if ($result->status === 201) {
            $this->bidFeedback = 'Bid accepted. You are now leading at AED '.number_format($result->body['bid']['amount_aed']).'.';
            $this->bidAmount = '';
            $this->dispatch('wallet-changed');
        } else {
            $this->bidError = $result->body['error']['message'];
        }

        $this->idempotencyKey = (string) Str::uuid();
    }

    public function render(): View
    {
        $dealer = Dealer::query()->with('wallet')->find(session('demo_dealer_id'));
        $listing = AuctionListing::query()->with('bids.dealer')->findOrFail($this->listingId);

        return view('livewire.listing-detail', [
            'listing' => $listing,
            'selectedDealer' => $dealer,
            'availableLimitAed' => $dealer?->wallet?->summary()['available_aed'],
            'standings' => $this->standings($listing->bids),
        ]);
    }

    /**
     * @param  Collection<int, Bid>  $bids
     * @return Collection<int, array{rank: int, bid: Bid, movement: string, places: int}>
     */
    private function standings(Collection $bids): Collection
    {
        $orderedBids = $bids->sortByDesc('amount_minor')->values();
        $previousPositions = $orderedBids->skip(1)->unique('dealer_id')->values();

        return $orderedBids->unique('dealer_id')->values()->map(function (Bid $bid, int $index) use ($previousPositions): array {
            $previousIndex = $previousPositions->search(fn (Bid $previousBid): bool => $previousBid->dealer_id === $bid->dealer_id);

            return [
                'rank' => $index + 1,
                'bid' => $bid,
                'movement' => $previousIndex === false ? 'new' : ($previousIndex > $index ? 'up' : ($previousIndex < $index ? 'down' : 'same')),
                'places' => $previousIndex === false ? 0 : abs($previousIndex - $index),
            ];
        });
    }
}
