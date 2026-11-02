<details x-data x-on:click.outside="$el.open = false" class="relative">
    <summary aria-label="Open profile menu" class="flex cursor-pointer list-none items-center gap-2 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 [&::-webkit-details-marker]:hidden">
        <img src="{{ asset('images/demo-dealer-avatar.svg') }}" alt="" class="h-10 w-10 rounded-full border border-blue-200 bg-blue-50 object-cover">
        <span class="hidden text-left sm:block">
            <strong class="block max-w-36 truncate text-sm leading-tight text-navy">{{ $dealer?->name ?? 'Demo account' }}</strong>
            <small class="block text-xs text-slate-500">{{ $dealer?->code ?? 'Choose a dealer' }}</small>
        </span>
        <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
    </summary>
    <div class="absolute right-0 z-50 mt-3 w-80 max-w-[calc(100vw-2.5rem)] border border-slate-200 bg-white shadow-lg">
        @if ($dealer)
            <div class="flex items-center gap-3 border-b border-slate-200 p-4">
                <img src="{{ asset('images/demo-dealer-avatar.svg') }}" alt="Demo dealer avatar" class="h-12 w-12 rounded-full border border-blue-200 bg-blue-50 object-cover">
                <div class="min-w-0">
                    <strong class="block truncate text-sm text-navy">{{ $dealer->name }}</strong>
                    <span class="block text-xs text-slate-500">Dealer ID {{ $dealer->code }} · Demo account</span>
                </div>
            </div>
            <div class="border-b border-slate-200 px-4 py-3 text-sm">
                <div class="flex justify-between gap-3"><span class="text-slate-500">Wallet account</span><strong class="text-navy">{{ $wallet ? '#'.str_pad((string) $wallet->id, 5, '0', STR_PAD_LEFT) : 'Unavailable' }}</strong></div>
                @if ($availableLimitAed !== null)
                    <div class="mt-1 flex justify-between gap-3"><span class="text-slate-500">Available limit</span><strong class="text-blue-700">AED {{ number_format($availableLimitAed) }}</strong></div>
                @endif
            </div>
            <a href="{{ route('wallet.manage') }}" class="block border-b border-slate-200 px-4 py-3 text-sm font-semibold text-navy hover:bg-blue-50 hover:text-blue-700">Wallet management →</a>
            <form wire:submit="switchDealer" class="p-4">
                <label for="profile-switch-dealer" class="block text-sm font-semibold text-navy">Switch account</label>
                <select id="profile-switch-dealer" wire:model="switchToCode" class="mt-2 w-full border border-slate-300 bg-white px-3 py-2 text-sm text-navy focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600">
                    <option value="">Select another demo dealer</option>
                    @foreach ($otherDealers as $otherDealer)
                        <option value="{{ $otherDealer->code }}">{{ $otherDealer->name }} · {{ $otherDealer->code }}</option>
                    @endforeach
                </select>
                @error('switchToCode') <p class="mt-2 text-xs text-red-700" role="alert">Choose another demo dealer.</p> @enderror
                <button type="submit" class="mt-3 w-full bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">Switch dealer</button>
            </form>
        @else
            <div class="p-4">
                <strong class="text-sm text-navy">Browsing as a visitor</strong>
                <p class="mt-1 text-xs text-slate-500">Choose a fictional demo dealer to bid and view a wallet.</p>
                <a href="{{ route('demo.dealer') }}" class="mt-4 block bg-blue-600 px-4 py-2 text-center text-sm font-bold text-white hover:bg-blue-700">Choose demo dealer</a>
                <a href="{{ route('wallet.manage') }}" class="mt-3 block text-center text-sm font-semibold text-blue-700 hover:underline">Wallet management</a>
            </div>
        @endif
    </div>
</details>
