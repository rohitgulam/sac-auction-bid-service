<?php

namespace App\Livewire;

use App\Models\Dealer;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class DemoDealerChooser extends Component
{
    public string $code = '';

    public function mount(): void
    {
        $this->code = (string) session('demo_dealer_code', '');
    }

    public function selectDealer(): void
    {
        $validated = $this->validate([
            'code' => ['required', 'string', Rule::exists('dealers', 'code')],
        ]);

        $dealer = Dealer::query()->where('code', $validated['code'])->firstOrFail();
        session()->put('demo_dealer_id', $dealer->id);
        session()->put('demo_dealer_code', $dealer->code);
        session()->regenerate();

        $this->redirect(route('dashboard'));
    }

    public function render(): View
    {
        return view('livewire.demo-dealer-chooser', [
            'dealers' => Dealer::query()->orderBy('code')->get(),
        ]);
    }
}
