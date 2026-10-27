<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Input bahan baku</p>
            <h1 class="page-title">Buat batch pemotongan ayam</h1>
            <p class="page-description">Mulai siklus produksi dengan mencatat penerimaan ayam hidup, berat timbang kotor, jumlah ekor, dan biaya pembelian.</p>
        </div>
        <a href="{{ route('productions.index') }}" class="btn">Kembali ke batch</a>
    </div>
    <x-feedback/>

    <form wire:submit="save" class="grid items-start gap-6 lg:grid-cols-[1fr_320px]">
        <!-- Kolom Kiri: Detail Bahan Baku Batch -->
        <section class="panel space-y-6 p-6">
            <h2 class="text-sm font-semibold">Identitas & penerimaan bahan baku</h2>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="field">
                    Nomor / kode batch *
                    <input wire:model="batch_number" class="input font-mono font-semibold" required>
                    @error('batch_number') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="field">
                    Tanggal produksi *
                    <input wire:model="production_date" type="date" class="input" required>
                    @error('production_date') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="field">
                    Total berat ayam hidup (kg) *
                    <input wire:model="live_weight_kg" type="number" step="0.01" class="input font-semibold" placeholder="Contoh: 500" required>
                    <span class="text-[11px] leading-5 text-brand-textGray">Berat timbang kotor saat ayam hidup tiba di RPH.</span>
                    @error('live_weight_kg') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="field">
                    Jumlah ekor (opsional)
                    <input wire:model="live_bird_count" type="number" class="input" placeholder="Contoh: 250">
                    <span class="text-[11px] leading-5 text-brand-textGray">Estimasi jumlah ayam hidup dalam batch ini.</span>
                    @error('live_bird_count') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="field">
                    Biaya pembelian ayam hidup (Rp) *
                    <input wire:model="raw_material_cost" type="number" step="1000" class="input font-semibold text-brand-primary" placeholder="Contoh: 11000000" required>
                    <span class="text-[11px] leading-5 text-brand-textGray">Total tagihan / nota beli ayam hidup ke peternak.</span>
                    @error('raw_material_cost') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="field">
                    Biaya operasional awal (Rp, opsional)
                    <input wire:model="additional_cost" type="number" step="1000" class="input" placeholder="0">
                    <span class="text-[11px] leading-5 text-brand-textGray">Misal: biaya angkut / bongkar ayam hidup.</span>
                    @error('additional_cost') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <label class="field">
                Catatan / keterangan batch (opsional)
                <textarea wire:model="notes" rows="3" class="input" placeholder="Catatan nama kandang, supplier, armada angkut, kondisi ayam..."></textarea>
            </label>
        </section>

        <!-- Kolom Kanan: Panduan Tahapan Kerja Sesuai Refrensi -->
        <aside class="panel space-y-5 p-6 self-start">
            <h2 class="text-sm font-semibold">Tahapan alur produksi</h2>

            <ol class="space-y-4">
                <li class="flex items-center gap-3 text-xs">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#eef4ff] text-[#00478a] font-bold">1</span>
                    Penerimaan & Timbang Ayam Hidup
                </li>
                <li class="flex items-center gap-3 text-xs">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#eef4ff] text-[#00478a] font-bold">2</span>
                    Penyembelihan & Pembersihan Bulu
                </li>
                <li class="flex items-center gap-3 text-xs">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#eef4ff] text-[#00478a] font-bold">3</span>
                    Penimbangan Karkas & Hitung Rendemen
                </li>
                <li class="flex items-center gap-3 text-xs">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#eef4ff] text-[#00478a] font-bold">4</span>
                    Alokasi HPP Otomatis Masuk ke Stok
                </li>
            </ol>

            <p class="border-t border-[#eaeef2] pt-4 text-xs leading-6 text-brand-textGray">
                Batch dimulai dari status Sedang Diproses. Hasil timbangan karkas bersih baru akan dimasukkan ke stok produk setelah Anda mengonfirmasi penimbangan akhir.
            </p>

            <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                <x-icon name="check"/>Simpan & mulai proses
            </button>
        </aside>
    </form>
</div>
