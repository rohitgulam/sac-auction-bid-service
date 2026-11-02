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
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-4 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-navy no-underline">
                <span class="flex h-10 w-10 items-center justify-center bg-blue-600 text-lg font-bold text-white">A</span>
                <span>
                    <strong class="block text-base leading-tight tracking-tight">Auction Bid Service</strong>
                    <small class="block text-xs font-medium text-slate-500">Independent dealer demo</small>
                </span>
            </a>
            <livewire:demo-dealer-chooser />
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
