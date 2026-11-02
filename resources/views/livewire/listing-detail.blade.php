<div>
    @php($highBid = $listing->highBid())
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">← Back to auction floor</a>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(300px,1fr)]">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex min-h-56 items-center justify-center bg-gradient-to-br from-blue-100 via-slate-100 to-indigo-100 p-8">
                    @if ($listing->kind === 'plate')
                        <div class="rounded-2xl border-8 border-white bg-white px-8 py-5 text-3xl font-black tracking-widest text-navy shadow-lg">{{ $listing->plate_number }}</div>
                    @else
                        <div class="flex h-32 w-52 items-center justify-center rounded-3xl border-8 border-white/80 bg-white/40 text-7xl text-blue-700 shadow-sm" aria-hidden="true">◇</div>
                    @endif
                </div>
                <div class="p-6 sm:p-8">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-slate-600">{{ $listing->lot_number }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $listing->isOpen() ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-500' }}">{{ $listing->isOpen() ? ucfirst($listing->offer_type).' Offer' : 'Closed' }}</span>
                    </div>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-navy">{{ $listing->title }}</h1>
                    <p class="mt-2 text-slate-500">{{ $listing->subtitle }} · {{ $listing->location }}</p>

                    @if ($listing->kind === 'vehicle')
                        <h2 class="mt-8 text-lg font-bold text-navy">Inspection summary</h2>
                        <p class="mt-1 text-sm text-slate-500">Fictional inspection data for this portfolio listing.</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 p-4"><span class="block text-xs uppercase tracking-wide text-slate-500">Model year</span><strong class="mt-1 block text-navy">{{ $listing->vehicle_year }}</strong></div>
                            <div class="rounded-xl bg-slate-50 p-4"><span class="block text-xs uppercase tracking-wide text-slate-500">Mileage</span><strong class="mt-1 block text-navy">{{ number_format($listing->mileage_km) }} km</strong></div>
                            @foreach ($listing->inspection_summary ?? [] as $label => $value)
                                <div class="rounded-xl bg-slate-50 p-4"><span class="block text-xs uppercase tracking-wide text-slate-500">{{ ucfirst($label) }}</span><strong class="mt-1 block font-semibold text-navy">{{ $value }}</strong></div>
                            @endforeach
                        </div>
                    @else
                        <h2 class="mt-8 text-lg font-bold text-navy">Plate details</h2>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 p-4"><span class="block text-xs uppercase tracking-wide text-slate-500">Display code</span><strong class="mt-1 block text-navy">{{ $listing->plate_number }}</strong></div>
                            <div class="rounded-xl bg-slate-50 p-4"><span class="block text-xs uppercase tracking-wide text-slate-500">Registry</span><strong class="mt-1 block text-navy">Fictional demo registry</strong></div>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">{{ $highBid ? 'Current high bid' : 'Opening amount' }}</p>
                <div class="mt-2 text-4xl font-bold tracking-tight text-navy">AED {{ number_format(intdiv($highBid?->amount_minor ?? $listing->opening_amount_minor, 100)) }}</div>
                @if ($highBid)
                    <p class="mt-2 text-sm text-slate-500">Leader: <strong class="text-blue-700">{{ $highBid->dealer->code }}</strong> @if (session('demo_dealer_id') === $highBid->dealer_id) <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-bold text-blue-700">You</span> @endif</p>
                @endif
                <div class="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-600">
                    <div class="flex justify-between gap-3"><span>Offer</span><strong>{{ ucfirst($listing->offer_type) }} Offer</strong></div>
                    <div class="mt-2 flex justify-between gap-3"><span>Closes</span><strong>{{ $listing->ends_at->format('d M Y, H:i') }} UTC</strong></div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-navy">Bid history</h2>
                <p class="mt-1 text-sm text-slate-500">Bidder codes identify fictional demo dealers.</p>
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse ($listing->bids->sortByDesc('amount_minor') as $bid)
                        <div class="flex items-center justify-between gap-4 py-3">
                            <div>
                                <strong class="text-sm text-navy">{{ $bid->dealer->code }}</strong>
                                @if (session('demo_dealer_id') === $bid->dealer_id)
                                    <span class="ml-1 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-bold text-blue-700">You</span>
                                @endif
                                <span class="block text-xs text-slate-400">{{ $bid->created_at->format('d M, H:i') }} UTC</span>
                            </div>
                            <strong class="text-sm text-navy">AED {{ number_format(intdiv($bid->amount_minor, 100)) }}</strong>
                        </div>
                    @empty
                        <p class="py-5 text-sm text-slate-500">No bids yet on this listing.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
