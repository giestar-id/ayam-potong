<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Manajemen Operasional, Produksi Pemotongan Ayam & POS Kasir Terpadu.">
    <meta name="theme-color" content="#00478a">
    <title>{{ $title ?? 'Ruang Kerja' }} · {{ \App\Models\BusinessSetting::current()->business_name }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
@php
    $biz = \App\Models\BusinessSetting::current();
    $groups = [
        'Ringkasan' => [
            ['dashboard', [], 'Dashboard', 'grid', request()->routeIs('dashboard')],
            ['sales.pos', [], 'Kasir / POS', 'sales', request()->routeIs('sales.pos')],
        ],
        'Operasional' => [
            ['products.index', [], 'Produk & Stok', 'box', request()->routeIs('products.*')],
            ['productions.index', [], 'Produksi Ayam', 'leaf', request()->routeIs('productions.*')],
            ['sales.index', [], 'Penjualan & Piutang', 'clock', request()->routeIs('sales.index')],
            ['customers.index', [], 'Pelanggan', 'users', request()->routeIs('customers.*')],
        ],
        'Keuangan & Tim' => [
            ['cash.index', ['direction' => 'Pemasukan'], 'Pemasukan', 'down', request()->routeIs('cash.*') && request('direction', 'Pemasukan') === 'Pemasukan'],
            ['cash.index', ['direction' => 'Pengeluaran'], 'Pengeluaran', 'up', request()->routeIs('cash.*') && request('direction') === 'Pengeluaran'],
            ['payrolls.index', [], 'Pembayaran Gaji', 'wallet', request()->routeIs('payrolls.*')],
            ['employees.index', [], 'Karyawan', 'users', request()->routeIs('employees.*')],
        ],
    ];
@endphp
<body x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
<x-notification/>
<x-confirmation-dialog/>
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[60] focus:rounded-lg focus:bg-white focus:p-3">Langsung ke konten</a>
<div x-show="menuOpen" x-cloak @click="menuOpen = false" class="fixed inset-0 z-40 bg-[#001a41]/40 backdrop-blur-sm lg:hidden"></div>
<aside id="main-navigation" :class="menuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="no-print fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-brand-sidebar text-white shadow-lg transition-transform duration-200">
    <a href="{{ route('dashboard') }}" class="flex h-16 shrink-0 items-center gap-3 px-5">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 text-white shadow-sm">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
        </span>
        <span class="truncate font-heading text-[16px] font-bold tracking-tight">{{ $biz->business_name }}</span>
    </a>
    <nav aria-label="Navigasi utama" class="flex-1 overflow-y-auto px-2 pb-5">
        @foreach($groups as $group => $links)
            <p class="mb-2 mt-5 px-4 text-[10px] font-semibold uppercase tracking-[.1em] text-[#adc6ff]">{{ $group }}</p>
            <div class="space-y-1">
                @foreach($links as [$route, $params, $label, $icon, $active])
                    <a href="{{ route($route, $params) }}" @class(['nav-link', 'is-active' => $active]) @if($active) aria-current="page" @endif><x-icon :name="$icon"/>{{ $label }}</a>
                @endforeach
            </div>
        @endforeach
    </nav>
    <div class="shrink-0 space-y-1 border-t border-white/10 px-2 pb-4 pt-3">
        <a href="{{ route('settings') }}" @class(['nav-link', 'is-active' => request()->routeIs('settings')])><x-icon name="settings"/>Pengaturan</a>
        <form action="{{ route('logout') }}" method="POST">@csrf<button class="nav-link w-full" type="submit"><x-icon name="logout"/>Keluar</button></form>
        <a href="{{ route('settings') }}" class="mx-1 mt-3 flex items-center gap-3 rounded-xl bg-brand-secondary px-3 py-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#d8e2ff] text-sm font-semibold text-brand-primary">{{ mb_substr(auth()->user()->name ?? 'P', 0, 1) }}</span>
            <span class="min-w-0"><span class="block truncate text-[13px] font-medium">{{ auth()->user()->name ?? 'Owner' }}</span><span class="mt-0.5 block text-[11px] text-[#adc6ff]">Pemilik Usaha</span></span>
        </a>
    </div>
</aside>
<div class="min-h-dvh lg:pl-64">
    <header class="no-print sticky top-0 z-[35] flex h-16 items-center justify-between gap-3 bg-white/95 px-4 shadow-[0_1px_8px_rgba(0,0,0,0.04)] backdrop-blur-xl sm:px-6">
        <button @click="menuOpen = !menuOpen" :aria-expanded="menuOpen" aria-controls="main-navigation" aria-label="Buka navigasi" class="btn shrink-0 rounded-lg border-0 px-2 lg:hidden"><x-icon name="menu"/></button>
        <div class="ml-auto flex shrink-0 items-center gap-3">
            <span class="hidden items-center gap-2 rounded-lg bg-[#f0f4f8] px-3 py-2 text-xs text-brand-textGray xl:flex"><x-icon name="calendar"/>{{ now('Asia/Jakarta')->locale('id')->translatedFormat('d F Y') }}</span>
            <a href="{{ route('settings') }}" aria-label="Pengaturan akun" class="flex h-8 w-8 items-center justify-center rounded-full bg-[#d8e2ff] text-xs font-semibold text-brand-primary">{{ mb_substr(auth()->user()->name ?? 'P', 0, 1) }}</a>
        </div>
    </header>
    <main id="main-content" class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6">{{ $slot }}</main>
    <footer class="no-print mx-4 flex justify-between gap-3 border-t border-[#dfe3e7] py-5 text-[10px] text-brand-textGray sm:mx-6">
        <span>{{ $biz->business_name }} · Sistem Manajemen & POS Ayam Potong</span>
        <span>Waktu Indonesia Barat (WIB)</span>
    </footer>
</div>
@livewireScripts
</body>
</html>
