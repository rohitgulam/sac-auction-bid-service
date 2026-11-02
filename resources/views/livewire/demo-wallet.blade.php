<div wire:poll.2s>
    @if ($walletSummary)
        <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-label="Demo wallet">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-bold text-navy">Your wallet</h2>
                <span class="text-xs font-semibold text-blue-700">{{ $dealer->code }}</span>
            </div>
            <dl class="mt-3 text-sm">
                <div class="detail-row"><dt>Deposit</dt><dd data-live-key="wallet-deposit" data-live-value="{{ $walletSummary['deposit_aed'] }}" aria-live="polite">AED {{ number_format($walletSummary['deposit_aed']) }}</dd></div>
                <div class="detail-row"><dt>Active reservations</dt><dd data-live-key="wallet-reserved" data-live-value="{{ $walletSummary['reserved_aed'] }}" aria-live="polite">AED {{ number_format($walletSummary['reserved_aed']) }}</dd></div>
                <div class="detail-row"><dt>Available limit</dt><dd data-live-key="wallet-available" data-live-value="{{ $walletSummary['available_aed'] }}" aria-live="polite" class="text-blue-700">AED {{ number_format($walletSummary['available_aed']) }}</dd></div>
            </dl>
            <div class="mt-5 border-t border-slate-200 pt-4">
                <h3 class="text-sm font-bold text-navy">Simulate a deposit top-up</h3>
                <p class="mt-1 text-xs text-slate-500">Demo only. No money moves. No payment or personal details are collected.</p>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    @foreach ($amountsAed as $amountAed)
                        <button type="button" wire:click="topUp({{ $amountAed }})" wire:loading.attr="disabled" class="{{ $loop->last ? 'bg-blue-600 text-white hover:bg-blue-700' : 'border border-blue-600 text-blue-700 hover:bg-blue-50' }} px-3 py-2 text-sm font-bold disabled:opacity-50">+ AED {{ number_format($amountAed) }}</button>
                    @endforeach
                </div>
                @if ($feedback)
                    <p class="mt-3 border-l-4 border-blue-600 bg-blue-50 px-3 py-2 text-sm text-blue-800" role="status">{{ $feedback }}</p>
                @endif
                @if ($error)
                    <p class="mt-3 border-l-4 border-red-600 bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ $error }}</p>
                @endif
            </div>
            <div class="mt-5 border-t border-slate-200 pt-4">
                <h3 class="text-sm font-bold text-navy">Wallet activity</h3>
                <div class="mt-2 max-h-44 divide-y divide-slate-100 overflow-y-auto">
                    @forelse ($activity as $entry)
                        <div class="flex items-center justify-between gap-3 py-2 text-sm">
                            <div><strong class="text-navy">Demo top-up</strong><span class="block text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($entry['created_at'])->format('d M Y, H:i') }} UTC</span></div>
                            <strong class="whitespace-nowrap text-blue-700">+ AED {{ number_format($entry['amount_aed']) }}</strong>
                        </div>
                    @empty
                        <p class="py-3 text-sm text-slate-500">No wallet activity yet.</p>
                    @endforelse
                </div>
            </div>
        </section>
    @endif
</div>
