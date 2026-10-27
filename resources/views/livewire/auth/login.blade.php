<div class="grid overflow-hidden rounded-3xl bg-white shadow-soft lg:grid-cols-2">
    <aside class="relative hidden flex-col justify-between overflow-hidden bg-brand-primary p-10 text-white lg:flex">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
            </span>
            <span class="font-heading text-xl font-bold tracking-tight">{{ $businessName }}</span>
        </div>
        <div class="my-14">
            <p class="text-xs font-medium uppercase tracking-[.12em] text-[#adc6ff]">Sistem Manajemen & POS Kasir</p>
            <h2 class="mt-4 text-[30px] font-bold leading-[1.3] tracking-[-.02em]">Kelola Pemotongan, Stok, & Penjualan Ayam dalam Satu Tempat.</h2>
            <p class="mt-5 text-sm leading-7 text-[#d8e2ff]">Hitung rendemen & susut ayam hidup, sinkronisasi stok otomatis, kasir POS cepat, dan pantau piutang pelanggan.</p>
            <div class="mt-8 space-y-3">
                <div class="flex items-center gap-3 rounded-xl bg-white/10 p-3">
                    <x-icon name="leaf"/>
                    <span class="text-[13px] font-medium">Produksi & Yield Ayam Potong</span>
                    <x-icon name="check" class="ml-auto text-[#adc6ff]"/>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-white/10 p-3">
                    <x-icon name="sales"/>
                    <span class="text-[13px] font-medium">Kasir Cepat & Diskon Otomatis</span>
                    <x-icon name="check" class="ml-auto text-[#adc6ff]"/>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-white/10 p-3">
                    <x-icon name="wallet"/>
                    <span class="text-[13px] font-medium">Arus Kas & Piutang Terintegrasi</span>
                    <x-icon name="check" class="ml-auto text-[#adc6ff]"/>
                </div>
            </div>
        </div>
        <p class="text-xs text-[#adc6ff]">Sistem Multi-Platform Web & Mobile App</p>
    </aside>

    <section class="p-7 sm:p-10 lg:p-12">
        <div class="mb-8 flex items-center gap-3 lg:hidden">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-primary text-white">
                <x-icon name="leaf"/>
            </span>
            <span class="font-heading text-lg font-bold">{{ $businessName }}</span>
        </div>
        <p class="eyebrow">Masuk ke Ruang Kerja</p>
        <h1 class="page-title">Selamat Datang Kembali</h1>
        <p class="mb-7 mt-3 text-sm leading-6 text-brand-textGray">Silakan masuk menggunakan akun pemilik usaha untuk mengakses sistem.</p>

        <form wire:submit="login" class="space-y-5">
            <div>
                <label class="field" for="email">
                    Alamat Email
                    <input id="email" wire:model="email" type="email" class="input" required autofocus autocomplete="username" placeholder="admin@ayampotong.com">
                </label>
                @error('email') <span class="text-[11px] text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="field" x-data="{ show: false }">
                <label for="password">Kata Sandi</label>
                <div class="relative">
                    <input id="password" wire:model="password" :type="show ? 'text' : 'password'" type="password" class="input pr-24" required autocomplete="current-password" placeholder="Masukkan password">
                    <button type="button" @click="show = !show" :aria-pressed="show" class="absolute inset-y-0 right-3 text-[11px] font-medium text-brand-primary" x-text="show ? 'Sembunyikan' : 'Tampilkan'">Tampilkan</button>
                </div>
                @error('password') <span class="text-[11px] text-rose-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <label class="flex items-center gap-2 text-xs text-brand-textGray cursor-pointer">
                <input wire:model="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300">
                Ingat saya di perangkat ini
            </label>

            <button class="btn btn-primary w-full py-3 text-sm font-bold shadow-md" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Masuk ke Dashboard</span>
                <span wire:loading>Memverifikasi kredensial…</span>
                <x-icon name="arrow"/>
            </button>
        </form>

        <div class="mt-8 rounded-xl bg-blue-50/60 p-4 border border-blue-100 text-xs text-brand-textGray space-y-1">
            <p class="font-bold text-brand-primary">Akun Demo Standar:</p>
            <p>Email: <code class="bg-white px-1.5 py-0.5 rounded border border-blue-200 text-brand-textDark">admin@ayampotong.com</code></p>
            <p>Password: <code class="bg-white px-1.5 py-0.5 rounded border border-blue-200 text-brand-textDark">password</code></p>
        </div>
    </section>
</div>
