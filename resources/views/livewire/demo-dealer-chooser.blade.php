<div class="w-full lg:w-auto">
    <form wire:submit="selectDealer" class="flex flex-wrap items-end gap-2">
        <label class="block min-w-40 flex-1 lg:flex-none">
            <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Demo dealer</span>
            <select wire:model="code" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" aria-label="Choose demo dealer code">
                <option value="">Choose a bidder code</option>
                @foreach ($dealers as $dealer)
                    <option value="{{ $dealer->code }}">{{ $dealer->code }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">Enter demo</button>
        @error('code') <span class="w-full text-xs text-red-600" role="alert">Choose a valid demo dealer code.</span> @enderror
    </form>
    @if (session('demo_dealer_code'))
        <p class="mt-1 text-xs text-slate-500">Viewing as <strong class="text-blue-700">{{ session('demo_dealer_code') }}</strong> · public demo identity</p>
    @else
        <p class="mt-1 text-xs text-slate-500">No password needed. Each browser keeps its own selection.</p>
    @endif
</div>
