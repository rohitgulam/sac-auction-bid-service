<div>
    <section class="overflow-hidden rounded-3xl bg-navy px-6 py-8 text-white shadow-lg sm:px-10 sm:py-10">
        <div class="grid gap-8 lg:grid-cols-[1.4fr_1fr] lg:items-end">
            <div>
                <p class="mb-3 text-xs font-bold uppercase tracking-[0.22em] text-blue-300">Independent auction demonstration</p>
                <h1 class="max-w-2xl text-3xl font-bold tracking-tight sm:text-5xl">Find your next bid.</h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">Explore inspected vehicles and fictional number plates. Choose a demo dealer above when you're ready to join the auction.</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-2xl border border-white/15 bg-white/10 p-4">
                    <span class="block text-3xl font-bold">{{ $totalListings }}</span>
                    <span class="text-xs uppercase tracking-widest text-slate-300">Total lots</span>
                </div>
                <div class="rounded-2xl border border-white/15 bg-white/10 p-4">
                    <span class="block text-3xl font-bold">{{ $openListings }}</span>
                    <span class="text-xs uppercase tracking-widest text-slate-300">Open now</span>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-9" aria-labelledby="listings-heading">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Browse listings</p>
                <h2 id="listings-heading" class="mt-1 text-2xl font-bold tracking-tight text-navy">Auction floor</h2>
                <p class="mt-1 text-sm text-slate-500">Fictional lots with visible inspection or plate details.</p>
            </div>
            <div class="flex flex-wrap gap-2" aria-label="Filter listings">
                <button type="button" wire:click="$set('kind', 'all')" class="filter-pill {{ $kind === 'all' ? 'filter-pill-active' : '' }}">All lots</button>
                <button type="button" wire:click="$set('kind', 'vehicle')" class="filter-pill {{ $kind === 'vehicle' ? 'filter-pill-active' : '' }}">Vehicles</button>
                <button type="button" wire:click="$set('kind', 'plate')" class="filter-pill {{ $kind === 'plate' ? 'filter-pill-active' : '' }}">Plates</button>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" wire:click="$set('status', 'all')" class="filter-pill {{ $status === 'all' ? 'filter-pill-active' : '' }}">All states</button>
            <button type="button" wire:click="$set('status', 'open')" class="filter-pill {{ $status === 'open' ? 'filter-pill-active' : '' }}">Open</button>
            <button type="button" wire:click="$set('status', 'closed')" class="filter-pill {{ $status === 'closed' ? 'filter-pill-active' : '' }}">Closed</button>
            <span class="mx-1 hidden border-l border-slate-300 sm:block"></span>
            <button type="button" wire:click="$set('offer', 'all')" class="filter-pill {{ $offer === 'all' ? 'filter-pill-active' : '' }}">All offers</button>
            <button type="button" wire:click="$set('offer', 'live')" class="filter-pill {{ $offer === 'live' ? 'filter-pill-active' : '' }}">Live Offers</button>
            <button type="button" wire:click="$set('offer', 'deal')" class="filter-pill {{ $offer === 'deal' ? 'filter-pill-active' : '' }}">Deal Offers</button>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($listings as $listing)
                @php($highBid = $listing->highBid())
                <article wire:key="listing-{{ $listing->id }}" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-lg">
                    <div class="flex h-36 items-center justify-center bg-gradient-to-br {{ $listing->kind === 'vehicle' ? 'from-blue-100 via-slate-100 to-indigo-100' : 'from-slate-200 via-blue-50 to-sky-100' }}">
                        @if ($listing->kind === 'vehicle')
                            <div class="flex h-20 w-32 items-center justify-center rounded-2xl border-4 border-white/80 bg-white/30 text-5xl text-blue-700 shadow-sm" aria-hidden="true">◇</div>
                        @else
                            <div class="rounded-xl border-4 border-white bg-white px-5 py-3 text-xl font-black tracking-widest text-navy shadow-md">{{ $listing->plate_number }}</div>
                        @endif
                    </div>
                    <div class="p-5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">{{ $listing->lot_number }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $listing->isOpen() ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-500' }}">{{ $listing->isOpen() ? ucfirst($listing->offer_type).' Offer' : 'Closed' }}</span>
                        </div>
                        <h3 class="mt-3 text-lg font-bold text-navy">{{ $listing->title }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $listing->subtitle }} · {{ $listing->location }}</p>
                        <div class="mt-5 flex items-end justify-between border-t border-slate-100 pt-4">
                            <div>
                                <span class="block text-xs font-medium uppercase tracking-wide text-slate-400">{{ $highBid ? 'Current high bid' : 'Opening amount' }}</span>
                                <strong class="mt-1 block text-xl text-navy">AED {{ number_format(intdiv($highBid?->amount_minor ?? $listing->opening_amount_minor, 100)) }}</strong>
                            </div>
                            <span class="text-right text-xs text-slate-500">{{ $listing->bids->count() }} bids<br>{{ $listing->ends_at->format('d M, H:i') }} UTC</span>
                        </div>
                        <a href="{{ route('listings.show', $listing) }}" class="mt-5 block rounded-lg bg-blue-600 px-4 py-2.5 text-center text-sm font-bold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">View listing <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center text-slate-500">No listings match these filters.</div>
            @endforelse
        </div>
    </section>
</div>
