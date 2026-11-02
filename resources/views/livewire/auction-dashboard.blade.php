<div wire:poll.2s>
    <section class="border-b border-slate-200 pb-6" aria-labelledby="listings-heading">
        <div class="flex flex-wrap items-end justify-between gap-5">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-blue-700">Auction inventory</p>
                <h1 id="listings-heading" class="mt-1 text-3xl font-bold tracking-tight text-navy">Available listings</h1>
                <p class="mt-2 text-sm text-slate-600">Browse inspected demo lots. Choose a demo dealer from the profile menu to take part.</p>
            </div>
            <div class="flex gap-5 text-sm">
                <div><strong class="block text-xl text-navy">{{ $openListings }}</strong><span class="text-slate-500">Open now</span></div>
                <div class="border-l border-slate-200 pl-5"><strong class="block text-xl text-navy">{{ $totalListings }}</strong><span class="text-slate-500">Total lots</span></div>
            </div>
        </div>
    </section>

    <section class="pt-6" aria-label="Listing filters">
        <div class="flex flex-wrap gap-x-5 gap-y-3 border-b border-slate-200">
            <button type="button" wire:click="$set('kind', 'all')" class="filter-tab {{ $kind === 'all' ? 'filter-tab-active' : '' }}">All lots</button>
            <button type="button" wire:click="$set('kind', 'vehicle')" class="filter-tab {{ $kind === 'vehicle' ? 'filter-tab-active' : '' }}">Vehicles</button>
            <button type="button" wire:click="$set('kind', 'plate')" class="filter-tab {{ $kind === 'plate' ? 'filter-tab-active' : '' }}">Number plates</button>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" wire:click="$set('status', 'all')" class="filter-chip {{ $status === 'all' ? 'filter-chip-active' : '' }}">Any status</button>
            <button type="button" wire:click="$set('status', 'open')" class="filter-chip {{ $status === 'open' ? 'filter-chip-active' : '' }}">Open</button>
            <button type="button" wire:click="$set('status', 'closed')" class="filter-chip {{ $status === 'closed' ? 'filter-chip-active' : '' }}">Closed</button>
            <span class="mx-1 self-center text-slate-300">|</span>
            <button type="button" wire:click="$set('offer', 'all')" class="filter-chip {{ $offer === 'all' ? 'filter-chip-active' : '' }}">Any offer</button>
            <button type="button" wire:click="$set('offer', 'live')" class="filter-chip {{ $offer === 'live' ? 'filter-chip-active' : '' }}">Live</button>
            <button type="button" wire:click="$set('offer', 'deal')" class="filter-chip {{ $offer === 'deal' ? 'filter-chip-active' : '' }}">Deal</button>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($listings as $listing)
                @php($highBid = $listing->highBid())
                <article wire:key="listing-{{ $listing->id }}" data-auction-card="{{ $listing->id }}" class="overflow-hidden border border-slate-200 bg-white shadow-sm">
                    <a href="{{ route('listings.show', $listing) }}" class="block focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-label="View {{ $listing->title }}">
                        @if ($listing->photoUrl())
                            <img src="{{ $listing->photoUrl() }}" alt="Representative photo of a {{ $listing->title }}" loading="lazy" class="h-44 w-full bg-slate-100 object-cover">
                        @else
                            <div class="flex h-44 items-center justify-center bg-[#eaf3f8] px-5"><span class="border-2 border-navy bg-white px-6 py-3 text-2xl font-bold tracking-wider text-navy">{{ $listing->plate_number }}</span></div>
                        @endif
                    </a>
                    <div class="p-4">
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="font-semibold text-slate-500">{{ $listing->lot_number }}</span>
                            <span class="font-semibold {{ $listing->isOpen() ? 'text-blue-700' : 'text-slate-500' }}">{{ $listing->isOpen() ? ucfirst($listing->offer_type).' offer · Open' : 'Closed' }}</span>
                        </div>
                        <h2 class="mt-2 text-lg font-bold text-navy"><a href="{{ route('listings.show', $listing) }}" class="hover:text-blue-700">{{ $listing->title }}</a></h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $listing->subtitle }} · {{ $listing->location }}</p>
                        <div class="mt-4 flex items-end justify-between gap-3 border-t border-slate-100 pt-3">
                            <div><span class="block text-xs text-slate-500">{{ $highBid ? 'Current high bid' : 'Opening amount' }}</span><strong data-live-key="card-{{ $listing->id }}-high" data-live-value="{{ $highBid?->amount_minor ?? $listing->opening_amount_minor }}" class="text-xl text-navy">AED {{ number_format(intdiv($highBid?->amount_minor ?? $listing->opening_amount_minor, 100)) }}</strong><span data-live-key="card-{{ $listing->id }}-leader" data-live-value="{{ $highBid?->dealer_id ?? 'none' }}" aria-live="polite" class="block text-xs text-blue-700">@if ($highBid) Leader {{ $highBid->dealer->code }}@if (session('demo_dealer_id') === $highBid->dealer_id) · You @endif @else No leader yet @endif</span></div>
                            <div class="text-right text-xs text-slate-500">{{ $listing->bids_count }} bids<br>Ends {{ $listing->ends_at->format('d M, H:i') }} UTC</div>
                        </div>
                        @if ($listing->latest_bid_at)
                            <p class="mt-2 text-xs font-semibold text-blue-700">Recent bid activity</p>
                        @endif
                        <a href="{{ route('listings.show', $listing) }}" class="mt-4 inline-flex w-full items-center justify-center bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">View details <span class="ml-2" aria-hidden="true">→</span></a>
                    </div>
                </article>
            @empty
                <div class="col-span-full border border-slate-200 bg-white px-6 py-12 text-center text-slate-500">No listings match these filters.</div>
            @endforelse
        </div>
        <p class="mt-4 text-xs text-slate-500">Vehicle photos show representative real models; each auction lot and inspection is fictional.</p>
    </section>
</div>
