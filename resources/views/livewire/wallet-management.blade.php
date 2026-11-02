<div>
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-blue-700 hover:underline">← All listings</a>
    <div class="mt-5 max-w-2xl">
        <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Demo account</p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight text-navy">Wallet management</h1>
        <p class="mt-2 text-sm text-slate-600">Review your deposit, reservations, and simulated wallet activity.</p>
        @if ($selectedDealer)
            <div class="mt-6"><livewire:demo-wallet /></div>
        @else
            <div class="mt-6 border border-slate-200 bg-white p-6">
                <p class="text-sm text-slate-600">Choose a demo dealer to view and manage a wallet.</p>
                <a href="{{ route('demo.dealer') }}" class="mt-4 inline-block bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">Choose demo dealer</a>
            </div>
        @endif
    </div>
</div>
