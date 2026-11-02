<?php

namespace App\Livewire;

use App\Actions\SelectDemoDealer;
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

    public function selectDealer(SelectDemoDealer $selectDemoDealer): void
    {
        $validated = $this->validate([
            'code' => ['required', 'string', Rule::exists('dealers', 'code')],
        ]);

        $dealer = Dealer::query()->where('code', $validated['code'])->firstOrFail();
        $selectDemoDealer->handle($dealer);

        $this->redirect(route('dashboard'));
    }

    public function render(): View
    {
        return view('livewire.demo-dealer-chooser', [
            'dealers' => Dealer::query()->orderBy('code')->get(),
        ]);
    }
}
