<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0099df">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>{{ config('app.name') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="min-h-screen bg-[#f4f7f9] font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-navy no-underline">
                <span class="flex h-10 w-10 items-center justify-center bg-blue-600 text-lg font-bold text-white">A</span>
                <span>
                    <strong class="block text-base leading-tight tracking-tight">Auction Bid Service</strong>
                    <small class="block text-xs font-medium text-slate-500">Independent dealer demo</small>
                </span>
            </a>
            <details class="relative">
                <summary aria-label="Open profile menu" class="flex cursor-pointer list-none items-center gap-2 border border-slate-200 px-2.5 py-2 text-sm font-semibold text-navy hover:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 [&::-webkit-details-marker]:hidden">
                    <svg class="h-5 w-5 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="8" r="3.5" />
                        <path d="M4.5 20c.7-3.4 3.4-5.2 7.5-5.2s6.8 1.8 7.5 5.2" />
                    </svg>
                    <span class="hidden sm:inline">{{ session('demo_dealer_code') ?: 'Demo account' }}</span>
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                </summary>
                <nav aria-label="Profile menu" class="absolute right-0 z-50 mt-2 w-56 border border-slate-200 bg-white py-2 shadow-lg">
                    <p class="border-b border-slate-100 px-4 pb-2 text-xs text-slate-500">{{ session('demo_dealer_code') ? 'Demo dealer '.session('demo_dealer_code') : 'Browsing as a visitor' }}</p>
                    <a href="{{ route('wallet.manage') }}" class="block px-4 py-2.5 text-sm font-semibold text-navy hover:bg-blue-50 hover:text-blue-700">Wallet management</a>
                    <a href="{{ route('demo.dealer') }}" class="block px-4 py-2.5 text-sm font-semibold text-navy hover:bg-blue-50 hover:text-blue-700">{{ session('demo_dealer_code') ? 'Switch account' : 'Choose demo dealer' }}</a>
                </nav>
            </details>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-5 py-7 sm:px-8 lg:py-9">
        {{ $slot }}
    </main>

    <footer class="mt-12 border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-7 text-sm text-slate-500 sm:px-8 md:flex-row md:items-center md:justify-between">
            <span>Independent portfolio demonstration using fictional data. Vehicle photos are representative.</span>
            <span>All amounts in AED · No real transactions</span>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
