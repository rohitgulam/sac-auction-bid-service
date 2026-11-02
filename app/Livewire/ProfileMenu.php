<?php

namespace App\Livewire;

use App\Actions\SelectDemoDealer;
use App\Models\Dealer;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class ProfileMenu extends Component
{
    public string $switchToCode = '';

    #[On('wallet-changed')]
    public function refreshWallet(): void {}

    public function switchDealer(SelectDemoDealer $selectDemoDealer): void
    {
        if (! session()->has('demo_dealer_id')) {
            $this->addError('switchToCode', 'Choose a demo dealer first.');

            return;
        }

        $validated = $this->validate([
            'switchToCode' => ['required', 'string', Rule::exists('dealers', 'code'), Rule::notIn([session('demo_dealer_code')])],
        ]);
        $dealer = Dealer::query()->where('code', $validated['switchToCode'])->firstOrFail();
        $selectDemoDealer->handle($dealer);

        $this->redirect(route('dashboard'));
    }

    public function render(): View
    {
        $dealer = Dealer::query()->with('wallet')->find(session('demo_dealer_id'));

        return view('livewire.profile-menu', [
            'dealer' => $dealer,
            'wallet' => $dealer?->wallet,
            'availableLimitAed' => $dealer?->wallet?->summary()['available_aed'],
            'otherDealers' => $dealer
                ? Dealer::query()->whereKeyNot($dealer->id)->orderBy('code')->get()
                : collect(),
        ]);
    }
}
