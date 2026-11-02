<div>
    @if ($walletSummary)
        <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-label="Demo wallet summary">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-bold text-navy">Your wallet</h2>
                <span class="text-xs font-semibold text-blue-700">{{ $dealer->code }}</span>
            </div>
            <dl class="mt-3 text-sm">
                <div class="detail-row"><dt>Deposit</dt><dd>AED {{ number_format($walletSummary['deposit_aed']) }}</dd></div>
                <div class="detail-row"><dt>Active reservations</dt><dd>AED {{ number_format($walletSummary['reserved_aed']) }}</dd></div>
                <div class="detail-row"><dt>Available limit</dt><dd class="text-blue-700">AED {{ number_format($walletSummary['available_aed']) }}</dd></div>
            </dl>
            <a href="{{ route('wallet.manage') }}" class="mt-4 inline-block text-sm font-semibold text-blue-700 hover:underline">Manage wallet →</a>
        </section>
    @endif
</div>
