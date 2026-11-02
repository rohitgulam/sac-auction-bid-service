<?php

namespace App\Livewire;

use App\Actions\TopUpWallet;
use App\Models\Dealer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class DemoWallet extends Component
{
    public string $idempotencyKey = '';

    public ?string $feedback = null;

    public ?string $error = null;

    public function mount(): void
    {
        $this->idempotencyKey = (string) Str::uuid();
    }

    public function topUp(int $amountAed, TopUpWallet $topUpWallet): void
    {
        $dealer = Dealer::query()->with('wallet')->find(session('demo_dealer_id'));
        $this->feedback = null;
        $this->error = null;

        if (! $dealer?->wallet) {
            $this->error = 'Choose a demo dealer before topping up.';

            return;
        }

        if (! in_array($amountAed, TopUpWallet::ALLOWED_AMOUNTS_AED, true)) {
            $this->error = 'Choose AED 10,000 or AED 50,000.';

            return;
        }

        $result = $topUpWallet->handle($dealer, $amountAed * 100, $this->idempotencyKey);

        if ($result->status === 201) {
            $this->feedback = 'Simulated AED '.number_format($amountAed).' top-up added.';
        } else {
            $this->error = $result->body['error']['message'];
        }

        $this->idempotencyKey = (string) Str::uuid();
    }

    #[On('wallet-changed')]
    public function refreshWallet(): void
    {
        $this->feedback = null;
        $this->error = null;
    }

    public function render(): View
    {
        $dealer = Dealer::query()->with('wallet')->find(session('demo_dealer_id'));
        $wallet = $dealer?->wallet;

        return view('livewire.demo-wallet', [
            'dealer' => $dealer,
            'walletSummary' => $wallet?->summary(),
            'activity' => $wallet?->topUpActivity() ?? collect(),
            'amountsAed' => TopUpWallet::ALLOWED_AMOUNTS_AED,
        ]);
    }
}
