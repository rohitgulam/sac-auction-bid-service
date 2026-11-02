<div>
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-blue-700 hover:underline">← All listings</a>
    <div class="mt-5 max-w-xl">
        <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Demo account</p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight text-navy">{{ session('demo_dealer_id') ? 'Switch demo dealer' : 'Choose a demo dealer' }}</h1>
        <p class="mt-2 text-sm text-slate-600">Each bidder code has its own wallet and bids. Choose different codes in separate browsers to try the auction together. This is a public demo selection, not production authentication.</p>
        <form wire:submit="selectDealer" class="mt-6 border border-slate-200 bg-white p-6 shadow-sm">
            <label for="dealer-code" class="block text-sm font-semibold text-navy">Bidder code</label>
            <select id="dealer-code" wire:model="code" class="mt-2 w-full border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                <option value="">Choose a bidder code</option>
                @foreach ($dealers as $dealer)
                    <option value="{{ $dealer->code }}">{{ $dealer->code }}</option>
                @endforeach
            </select>
            @error('code') <p class="mt-2 text-sm text-red-700" role="alert">Choose a valid demo dealer code.</p> @enderror
            <button type="submit" class="mt-4 w-full bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">Continue as demo dealer</button>
        </form>
        @if (session('demo_dealer_code'))
            <p class="mt-4 text-sm text-slate-500">Current bidder code: <strong class="text-navy">{{ session('demo_dealer_code') }}</strong></p>
        @endif
    </div>
</div>
