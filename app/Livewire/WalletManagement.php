<?php

namespace App\Livewire;

use App\Models\Dealer;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class WalletManagement extends Component
{
    public function render(): View
    {
        return view('livewire.wallet-management', [
            'selectedDealer' => Dealer::query()->find(session('demo_dealer_id')),
        ]);
    }
}
