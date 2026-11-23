<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Persediaan</p>
            <h1 class="page-title">{{ $productId ? 'Detail & edit produk' : 'Tambah produk baru' }}</h1>
        </div>
        <a href="{{ route('products.index') }}" class="btn">Kembali ke produk</a>
    </div>
    <x-feedback/>

    <form wire:submit="save" class="grid items-start gap-6 lg:grid-cols-[1fr_320px] min-w-0 max-w-full">
        <!-- Kolom Kiri: Form Detail Produk -->
        <section class="panel space-y-6 p-4 sm:p-6 min-w-0 max-w-full overflow-hidden">
            <h2 class="text-sm font-semibold">Informasi produk</h2>

            <label class="field">
                Nama produk ayam *
                <input class="input" wire:model="name" maxlength="150" required placeholder="Contoh: Dada Fillet Segar, Paha Utuh, Sayap, dll.">
                @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            <div class="field min-w-0 max-w-full">
                <span class="font-medium text-brand-textDark">Foto / Gambar Produk</span>
                <div class="mt-2 flex flex-col sm:flex-row items-start sm:items-center gap-4 min-w-0 max-w-full">
                    <div class="flex items-center gap-3 shrink-0">
                        @if($image)
                            <div class="relative shrink-0">
                                <img src="{{ $image->temporaryUrl() }}" class="h-20 w-20 rounded-xl object-cover border border-brand-border shadow-sm" alt="Preview Gambar">
                                <button type="button" wire:click="removeImage" class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-white shadow hover:bg-red-700" title="Hapus foto baru">
                                    <x-icon name="close" class="h-3 w-3"/>
                                </button>
                            </div>
                        @elseif($existingImageUrl)
                            <div class="relative shrink-0">
                                <img src="{{ $existingImageUrl }}" class="h-20 w-20 rounded-xl object-cover border border-brand-border shadow-sm" alt="Gambar Produk">
                            </div>
                        @else
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-slate-400">
                                <x-icon name="box" class="h-8 w-8"/>
                            </div>
                        @endif

                        @if($image)
                            <button type="button" wire:click="removeImage" class="text-xs font-semibold text-red-600 hover:underline sm:hidden">
                                Batal foto
                            </button>
                        @endif
                    </div>

                    <div class="w-full flex-1 min-w-0 max-w-full">
                        <div class="relative max-w-full overflow-hidden">
                            <input type="file" wire:model="image" accept="image/*" class="block w-full max-w-full text-xs text-brand-textGray file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-primary file:text-white hover:file:bg-brand-primaryDark cursor-pointer truncate rounded-xl border border-[#dfe3e7] bg-[#f8fafc] p-1.5 focus:outline-none">
                        </div>
                        <div wire:loading wire:target="image" class="mt-1.5 flex items-center gap-1.5 text-xs font-semibold text-brand-primary">
                            <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span>Mengunggah foto...</span>
                        </div>
                        <p class="mt-1 text-[11px] text-brand-textGray leading-relaxed">Maksimal 2MB (JPG, PNG, WEBP). Gambar akan ditampilkan di kartu produk kasir POS.</p>
                        @error('image') <span class="mt-1 block text-xs text-red-600 font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

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
        <aside class="panel space-y-5 p-4 sm:p-6 self-start min-w-0 max-w-full overflow-hidden">
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
