<?php

namespace App\Http\Requests;

use App\Models\Dealer;
use App\Policies\AuctionListingPolicy;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PlaceBidRequest extends FormRequest
{
    public ?Dealer $demoDealer = null;

    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    public function authorize(): bool
    {
        $this->demoDealer = Dealer::query()->find($this->session()->get('demo_dealer_id'));

        return $this->demoDealer !== null
            && app(AuctionListingPolicy::class)->placeBid($this->demoDealer, $this->route('listing'));
    }

    protected function failedAuthorization(): void
    {
        $status = $this->demoDealer ? 403 : 401;

        throw new HttpResponseException(response()->json([
            'error' => $this->demoDealer
                ? ['code' => 'bid_forbidden', 'message' => 'This demo dealer cannot bid on this listing.']
                : ['code' => 'demo_dealer_required', 'message' => 'Select a demo dealer before placing a bid.'],
        ], $status));
    }

    /**
     * @return array{amount_aed: array<int, string>, idempotency_key: array<int, string>}
     */
    public function rules(): array
    {
        return [
            'amount_aed' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'idempotency_key' => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'error' => ['code' => 'invalid_bid', 'message' => 'Check the bid amount and Idempotency-Key.'],
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
