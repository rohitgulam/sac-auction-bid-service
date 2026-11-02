<?php

namespace App\Actions;

use App\Models\Dealer;

class SelectDemoDealer
{
    public function handle(Dealer $dealer): void
    {
        session()->put('demo_dealer_id', $dealer->id);
        session()->put('demo_dealer_code', $dealer->code);
        session()->regenerate();
    }
}
