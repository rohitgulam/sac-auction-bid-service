<?php

namespace App\Http\Requests;

use App\Actions\TopUpWallet;
use App\Models\Dealer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class TopUpWalletRequest extends FormRequest
{
    public ?Dealer $demoDealer = null;

    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    public function authorize(): bool
    {
        $this->demoDealer = Dealer::query()->with('wallet')->find($this->session()->get('demo_dealer_id'));

        return $this->demoDealer?->wallet !== null;
    }

    protected function failedAuthorization(): void
    {
        $selected = $this->demoDealer !== null;

        throw new HttpResponseException(response()->json([
            'error' => $selected
                ? ['code' => 'wallet_unavailable', 'message' => 'This demo dealer has no wallet.']
                : ['code' => 'demo_dealer_required', 'message' => 'Select a demo dealer before topping up.'],
        ], $selected ? 403 : 401));
    }

    /**
     * @return array{amount_aed: array<int, mixed>, idempotency_key: array<int, string>}
     */
    public function rules(): array
    {
        return [
            'amount_aed' => ['required', 'integer', Rule::in(TopUpWallet::ALLOWED_AMOUNTS_AED)],
            'idempotency_key' => ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'error' => ['code' => 'invalid_top_up', 'message' => 'Choose AED 10,000 or AED 50,000 and provide an Idempotency-Key.'],
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
