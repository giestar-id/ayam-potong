<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Persediaan</p>
            <h1 class="page-title">{{ $productId ? 'Detail & edit produk' : 'Tambah produk baru' }}</h1>
        </div>
        <a href="{{ route('products.index') }}" class="btn">Kembali ke produk</a>
    </div>
    <x-feedback/>

    <form wire:submit="save" class="grid items-start gap-6 lg:grid-cols-[1fr_320px]">
        <!-- Kolom Kiri: Form Detail Produk -->
        <section class="panel space-y-6 p-6">
            <h2 class="text-sm font-semibold">Informasi produk</h2>

            <label class="field">
                Nama produk ayam *
                <input class="input" wire:model="name" maxlength="150" required placeholder="Contoh: Dada Fillet Segar, Paha Utuh, Sayap, dll.">
                @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="field">
                    Harga jual standar (Rp / kg) *
                    <input class="input font-semibold" wire:model="selling_price" inputmode="decimal" required placeholder="Contoh: 38000">
                    @error('selling_price') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="field">
                    Batas stok minimum *
                    <input class="input" wire:model="min_stock" inputmode="decimal" placeholder="Contoh: 5 kg" required>
                    <span class="text-xs font-normal text-brand-textGray">Peringatan stok menipis muncul jika stok di bawah angka ini.</span>
                    @error('min_stock') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="field">
                    {{ $productId ? 'Stok saat ini' : 'Stok awal' }} *
                    <input class="input" wire:model="stock" inputmode="decimal" placeholder="Contoh: 10 kg" {{ $productId ? 'readonly' : '' }}>
                    @if($productId)
                        <span class="text-xs font-normal text-brand-textGray">Penambahan stok dilakukan lewat menu Stok atau Produksi.</span>
                    @endif
                    @error('stock') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                @if($productId)
                    <label class="field">
                        Modal rata-rata / HPP (Rp / kg)
                        <input class="input bg-brand-bgMain font-medium" wire:model="average_cost" inputmode="decimal" readonly placeholder="0">
                        <span class="text-xs font-normal text-brand-textGray">Dihitung otomatis dengan metode Moving Weighted Average.</span>
                    </label>
                @endif
            </div>
        </section>

        <!-- Kolom Kanan: Status & Panel Informasi Sesuai Refrensi -->
        <aside class="panel space-y-5 p-6 self-start">
            <h2 class="text-sm font-semibold">Status & visibilitas</h2>

            <label class="flex items-center gap-2 text-sm text-brand-textDark cursor-pointer">
                <input type="checkbox" wire:model="is_active">
                Produk aktif
            </label>
            <p class="text-xs leading-6 text-brand-textGray">
                Produk aktif dapat dipilih di kasir POS. Jika dinonaktifkan, data tetap tersimpan di riwayat tanpa mengganggu laporan lama.
            </p>

            <div class="rounded-xl bg-[#eef4ff] p-4 text-xs leading-6">
                <p class="font-semibold text-brand-primary">Kalkulasi modal HPP</p>
                <p class="mt-1 text-brand-textGray">
                    Modal rata-rata (HPP) diperbarui otomatis setiap kali ada pembelian stok masuk atau batch pemotongan ayam hidup selesai ditimbang.
                </p>
            </div>

            <p class="text-[11px] leading-5 text-brand-textGray">
                Input kuantitas berat mendukung angka desimal menggunakan titik atau koma (contoh: 12.5 kg).
            </p>

            <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                <x-icon name="check"/>{{ $productId ? 'Simpan perubahan' : 'Simpan produk' }}
            </button>

            @if($productId)
                <a href="{{ route('products.index') }}" class="btn w-full">Buka daftar produk</a>
            @endif
        </aside>
    </form>
</div>
