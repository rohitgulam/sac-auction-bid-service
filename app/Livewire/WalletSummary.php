<?php

namespace App\Livewire;

use App\Models\Dealer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class WalletSummary extends Component
{
    /** Livewire re-renders the summary when a bid changes the wallet. */
    #[On('wallet-changed')]
    public function refreshWallet(): void {}

    public function render(): View
    {
        $dealer = Dealer::query()->with('wallet')->find(session('demo_dealer_id'));

        return view('livewire.wallet-summary', [
            'dealer' => $dealer,
            'walletSummary' => $dealer?->wallet?->summary(),
        ]);
    }
}
