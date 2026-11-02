<div>
    @php($highBid = $listing->highBid())
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-blue-700 hover:underline">← All listings</a>

    <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1.6fr)_minmax(300px,1fr)]">
        <div class="space-y-5">
            <section class="overflow-hidden border border-slate-200 bg-white shadow-sm">
                @if ($listing->photoUrl())
                    <img src="{{ $listing->photoUrl() }}" alt="Representative photo of a {{ $listing->title }}" class="h-72 w-full bg-slate-100 object-cover sm:h-96">
                @else
                    <div class="flex h-72 items-center justify-center bg-[#eaf3f8]"><span class="border-2 border-navy bg-white px-8 py-5 text-3xl font-bold tracking-wider text-navy">{{ $listing->plate_number }}</span></div>
                @endif
                <div class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-center gap-3 text-xs font-semibold uppercase tracking-wide">
                        <span class="text-slate-500">{{ $listing->lot_number }}</span>
                        <span class="text-blue-700">{{ $listing->isOpen() ? ucfirst($listing->offer_type).' offer · Open' : 'Closed' }}</span>
                    </div>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-navy">{{ $listing->title }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ $listing->subtitle }} · {{ $listing->location }}</p>
                    @if ($listing->photoCredit())
                        <p class="mt-3 text-xs text-slate-500">Representative photo: <a href="{{ $listing->photoCredit()['source'] }}" class="underline hover:text-blue-700" target="_blank" rel="noopener noreferrer">{{ $listing->photoCredit()['photographer'] }} / Wikimedia Commons</a> · <a href="{{ $listing->photoCredit()['license_url'] }}" class="underline hover:text-blue-700" target="_blank" rel="noopener noreferrer">{{ $listing->photoCredit()['license'] }}</a>. Demo inspection data is fictional.</p>
                    @endif
                </div>
            </section>

            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-bold text-navy">{{ $listing->kind === 'vehicle' ? 'Inspection summary' : 'Plate details' }}</h2>
                @if ($listing->kind === 'vehicle')
                    <p class="mt-1 text-sm text-slate-500">Fictional details for this demo lot.</p>
                    <dl class="mt-4 grid gap-x-6 sm:grid-cols-2">
                        <div class="detail-row"><dt>Model year</dt><dd>{{ $listing->vehicle_year }}</dd></div>
                        <div class="detail-row"><dt>Mileage</dt><dd>{{ number_format($listing->mileage_km) }} km</dd></div>
                        @foreach ($listing->inspection_summary ?? [] as $label => $value)
                            <div class="detail-row"><dt>{{ ucfirst($label) }}</dt><dd>{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                @else
                    <dl class="mt-4"><div class="detail-row"><dt>Display code</dt><dd>{{ $listing->plate_number }}</dd></div><div class="detail-row"><dt>Registry</dt><dd>Fictional demo registry</dd></div></dl>
                @endif
            </section>
        </div>

        <div class="space-y-5">
            <livewire:wallet-summary />

            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <p class="text-sm text-slate-500">{{ $highBid ? 'Current high bid' : 'Opening amount' }}</p>
                <div class="mt-1 text-3xl font-bold text-navy">AED {{ number_format(intdiv($highBid?->amount_minor ?? $listing->opening_amount_minor, 100)) }}</div>
                @if ($highBid)
                    <p class="mt-2 text-sm text-slate-600">Leading bidder <strong class="text-blue-700">{{ $highBid->dealer->code }}</strong> @if (session('demo_dealer_id') === $highBid->dealer_id) <span class="font-bold text-blue-700">· You</span> @endif</p>
                @endif
                <dl class="mt-5 border-t border-slate-200 text-sm">
                    <div class="detail-row"><dt>Offer type</dt><dd>{{ ucfirst($listing->offer_type) }} offer</dd></div>
                    <div class="detail-row"><dt>Closes</dt><dd>{{ $listing->ends_at->format('d M Y, H:i') }} UTC</dd></div>
                </dl>
                @if ($listing->isOpen())
                    @if ($selectedDealer)
                        <form wire:submit="submitBid" class="mt-5 border-t border-slate-200 pt-5">
                            <label for="bid-amount" class="block text-sm font-semibold text-navy">Your bid in whole AED</label>
                            <p class="mt-1 text-xs text-slate-500">Enter at least AED {{ number_format(intdiv($highBid?->amount_minor ?? $listing->opening_amount_minor, 100) + 1) }}. Your leading bid reserves its full amount from your deposit.</p>
                            <div class="mt-3 flex gap-2">
                                <span class="flex items-center border border-slate-300 bg-slate-50 px-3 text-sm font-semibold text-slate-600">AED</span>
                                <input id="bid-amount" type="text" inputmode="numeric" autocomplete="off" wire:model="bidAmount" x-on:input="
                                    const original = $el.value;
                                    const before = $el.value.slice(0, $el.selectionStart).replace(/\D/g, '').length;
                                    $el.value = $el.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                    let position = 0, digits = 0;
                                    while (position < $el.value.length && digits < before) {
                                        if (/\d/.test($el.value[position])) digits++;
                                        position++;
                                    }
                                    $el.setSelectionRange(position, position);
                                    if ($el.value !== original) $el.dispatchEvent(new Event('input', { bubbles: true }));
                                " class="min-w-0 flex-1 border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600" required>
                            </div>
                            @error('bidAmount') <p class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                            <button type="submit" wire:loading.attr="disabled" class="mt-3 w-full bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">Place bid</button>
                        </form>
                    @else
                        <p class="mt-5 border-t border-slate-200 pt-4 text-sm text-slate-600">Choose a demo dealer above to place a bid.</p>
                    @endif
                @else
                    <p class="mt-5 border-t border-slate-200 pt-4 text-sm text-slate-600">Bidding has closed. The winning reservation remains active until settlement.</p>
                @endif
                @if ($bidFeedback)
                    <p class="mt-3 border-l-4 border-blue-600 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-800" role="status">{{ $bidFeedback }}</p>
                @endif
                @if ($bidError)
                    <p class="mt-3 border-l-4 border-red-600 bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ $bidError }}</p>
                @endif
            </section>
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-bold text-navy">Bid history</h2>
                <p class="mt-1 text-sm text-slate-500">Dealer codes keep demo identities private.</p>
                <div class="mt-3 divide-y divide-slate-100">
                    @forelse ($listing->bids->sortByDesc('amount_minor') as $bid)
                        <div class="flex items-center justify-between gap-4 py-3 text-sm">
                            <div><strong class="text-navy">{{ $bid->dealer->code }}</strong>@if (session('demo_dealer_id') === $bid->dealer_id) <span class="text-xs font-bold text-blue-700">· You</span> @endif<span class="block text-xs text-slate-500">{{ $bid->created_at->format('d M, H:i') }} UTC</span></div>
                            <strong class="text-navy">AED {{ number_format(intdiv($bid->amount_minor, 100)) }}</strong>
                        </div>
                    @empty
                        <p class="py-5 text-sm text-slate-500">No bids yet on this listing.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
