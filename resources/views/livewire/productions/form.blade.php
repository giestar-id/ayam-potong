<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Input bahan baku</p>
            <h1 class="page-title">Buat batch pemotongan ayam</h1>
        </div>
        <a href="{{ route('productions.index') }}" class="btn">Kembali ke batch</a>
    </div>
    <x-feedback/>

    <form wire:submit="save" class="grid items-start gap-6 lg:grid-cols-[1fr_320px]">
        <!-- Kolom Kiri: Input Pembelian Ayam Kiloan -->
        <section class="panel space-y-6 p-6">
            <h2 class="text-sm font-semibold text-brand-textDark">Penerimaan & pembelian ayam kiloan</h2>

            <!-- Baris 1: Kode Batch & Tanggal Otomatis (Hari Ini) -->
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="batch_number" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Nomor / kode batch *
                    </label>
                    <div class="relative flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 shadow-sm transition focus-within:border-brand-primary focus-within:ring-2 focus-within:ring-brand-primary/20">
                        <input id="batch_number" wire:model="batch_number" class="w-full border-0 bg-transparent p-0 font-mono text-sm font-semibold text-brand-textDark placeholder:font-normal placeholder:text-slate-400 outline-none focus:outline-none focus:ring-0" required>
                    </div>
                    @error('batch_number') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <span class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Tanggal produksi
                    </span>
                    <div class="flex h-[42px] items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-medium text-slate-600">
                        <span class="mr-2">📅</span>
                        Hari ini ({{ now()->translatedFormat('d F Y') }})
                    </div>
                </div>
            </div>

            <!-- Baris 2: Jumlah Kilo & Total Modal Beli (Harga per kg dihitung otomatis oleh sistem) -->
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="live_weight_kg" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Jumlah kilo ayam hidup (kg) *
                    </label>
                    <div class="relative flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 shadow-sm transition focus-within:border-brand-primary focus-within:ring-2 focus-within:ring-brand-primary/20">
                        <input id="live_weight_kg" wire:model.live.debounce.300ms="live_weight_kg" type="number" step="0.01" class="w-full border-0 bg-transparent p-0 text-sm font-semibold text-brand-primary placeholder:font-normal placeholder:text-slate-400 outline-none focus:outline-none focus:ring-0" placeholder="Contoh: 100" required>
                        <span class="ml-2 text-xs font-bold text-slate-400">kg</span>
                    </div>
                    <span class="mt-1 block text-[11px] leading-4 text-brand-textGray">Total timbangan kilo kotor saat ayam hidup tiba dari supplier.</span>
                    @error('live_weight_kg') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="total_cost" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Total modal pembelian ayam (Rp) *
                    </label>
                    <div class="relative flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 shadow-sm transition focus-within:border-brand-primary focus-within:ring-2 focus-within:ring-brand-primary/20">
                        <span class="mr-2 text-xs font-bold text-slate-500">Rp</span>
                        <input id="total_cost" wire:model.live.debounce.300ms="total_cost" type="number" step="1000" class="w-full border-0 bg-transparent p-0 text-sm font-semibold text-brand-primary placeholder:font-normal placeholder:text-slate-400 outline-none focus:outline-none focus:ring-0" placeholder="Contoh: 2200000" required>
                    </div>
                    <span class="mt-1 block text-[11px] leading-4 text-brand-textGray">Total uang yang dikeluarkan untuk membeli batch ayam ini.</span>
                    @error('total_cost') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Kalkulasi Otomatis Harga per kg oleh Sistem -->
            <div class="rounded-xl border border-blue-100 bg-[#eef4ff] p-4 text-xs">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="font-semibold text-brand-primary">Harga Beli per kg (Dihitung Otomatis oleh Sistem)</p>
                        <p class="mt-1 text-2xl font-bold text-brand-primary">
                            Rp {{ number_format($this->calculatedBuyPricePerKg, 0, ',', '.') }}
                            <span class="text-sm font-normal text-slate-600">/ kg</span>
                        </p>
                    </div>
                    <div class="rounded-lg bg-blue-100 px-3 py-1.5 text-xs font-bold text-brand-primary">
                        Dihitung Otomatis
                    </div>
                </div>
                <p class="mt-2 text-brand-textGray">
                    Rumus: Total Modal (Rp {{ number_format((float) $total_cost, 0, ',', '.') }}) ÷ Kilo Ayam ({{ (float) $live_weight_kg > 0 ? number_format((float) $live_weight_kg, 2, ',', '.') : '0' }} kg)
                </p>
            </div>

            <!-- Baris 3: Catatan / Supplier -->
            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Catatan / supplier (opsional)
                </label>
                <div class="relative flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 shadow-sm transition focus-within:border-brand-primary focus-within:ring-2 focus-within:ring-brand-primary/20">
                    <textarea id="notes" wire:model="notes" rows="2" class="w-full border-0 bg-transparent p-0 text-sm text-brand-textDark placeholder:font-normal placeholder:text-slate-400 outline-none focus:outline-none focus:ring-0" placeholder="Catatan supplier, armada angkut, kondisi ayam..."></textarea>
                </div>
            </div>
        </section>

        <!-- Kolom Kanan: Panduan Ringkas -->
        <aside class="panel space-y-5 p-6 self-start">
            <h2 class="text-sm font-semibold">Alur operasional</h2>

            <div class="space-y-3 text-xs leading-5 text-brand-textGray">
                <p>
                    <strong class="text-brand-textDark">1. Input Awal:</strong> Masukkan jumlah kilo ayam dan total modal pembelian. Sistem otomatis menghitung harga beli per kg.
                </p>
                <p>
                    <strong class="text-brand-textDark">2. Penjualan Harian:</strong> Transaksi kasir POS otomatis terhitung mengurangi stok dan menghasilkan omzet.
                </p>
                <p>
                    <strong class="text-brand-textDark">3. Update Sisa Ayam:</strong> Cukup timbang sisa ayam (kg) dan klik tombol update pada daftar batch.
                </p>
                <p>
                    <strong class="text-brand-textDark">4. Pantau Penyusutan & Laba:</strong> Sistem otomatis menghitung susut kilo dan keuntungan bersih harian.
                </p>
            </div>

            <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                <x-icon name="check"/>Simpan batch produksi
            </button>
        </aside>
    </form>
</div>
