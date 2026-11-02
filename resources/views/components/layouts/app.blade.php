<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#eef3f8">
    <title>{{ config('app.name') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white/95">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-5 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-navy no-underline">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-lg font-bold text-white shadow-sm">A</span>
                <span>
                    <strong class="block text-base leading-tight tracking-tight">Auction Bid Service</strong>
                    <small class="block text-xs font-medium uppercase tracking-[0.18em] text-slate-500">Fictional dealer demo</small>
                </span>
            </a>
            <livewire:demo-dealer-chooser />
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-5 py-8 sm:px-8 lg:py-12">
        {{ $slot }}
    </main>

    <footer class="mt-12 border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-7 text-sm text-slate-500 sm:px-8 md:flex-row md:items-center md:justify-between">
            <span>Independent portfolio demonstration using fictional data.</span>
            <span>All amounts in AED · No real transactions</span>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
