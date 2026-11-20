<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Persediaan</p>
            <h1 class="page-title">Produk & stok ayam</h1>
        </div>
        @can('owner')
        <a href="{{ route('products.form') }}" class="btn btn-primary"><x-icon name="plus"/>Tambah produk</a>
        @endcan
    </div>
    <x-feedback/>

    <!-- 4 Stat Cards using refrensi x-stat -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Jumlah varian produk" :value="$totalProduk . ' varian'" note="Produk aktif terdaftar" icon="grid"/>
        <x-stat label="Total stok kiloan (kg)" :value="number_format($totalStokKg, 2, ',', '.') . ' kg'" note="Varian berbasis timbangan" icon="box"/>
        <x-stat label="Total stok satuan (ekor)" :value="number_format($totalStokEkor, 0, ',', '.') . ' ekor'" note="Ayam utuh karkas" icon="box"/>
        <x-stat label="Produk stok menipis" :value="$produkMenipis . ' produk'" note="Pada atau di bawah batas minimum" icon="box"/>
    </div>

    <!-- Restock Modal (Moving Weighted Average Cost PRD 2.3) -->
    @if($restockProductId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-sm sm:backdrop-blur-md" x-data @keydown.escape.window="$wire.closeRestock()">
            <section role="dialog" aria-modal="true" class="panel relative max-h-[90dvh] overflow-y-auto p-4 shadow-2xl" style="max-width: 380px; width: 100%;">
                <div class="mb-2.5 flex items-start justify-between gap-2 border-b border-brand-border pb-2">
                    <div>
                        <p class="eyebrow text-[10px]">Restok produk</p>
                        <h2 class="text-sm font-bold truncate max-w-[220px]">Stok masuk: {{ $restockProductName }}</h2>
                    </div>
                    <button type="button" class="btn text-xs py-1 px-2" wire:click="closeRestock" aria-label="Tutup"><x-icon name="close"/></button>
                </div>

                <div class="mb-3 grid grid-cols-2 gap-2 rounded-lg bg-brand-bgMain p-2.5 text-xs">
                    <div>
                        <p class="text-[10px] text-brand-textGray">Stok saat ini</p>
                        <p class="mt-0.5 font-semibold">{{ number_format($currentStock, 2, ',', '.') }} {{ $restockUnit }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-brand-textGray">Satuan</p>
                        <p class="mt-0.5 font-semibold">{{ $restockUnit === 'kg' ? 'Kilogram (kg)' : 'Ekor' }}</p>
                    </div>
                </div>

                <form wire:submit="saveRestock" class="space-y-3">
                    <label class="field text-xs">
                        Jumlah masuk ({{ $restockUnit }}) *
                        <input wire:model="incomingQty" type="number" step="0.01" class="input text-xs" placeholder="Contoh: 10" required>
                        @error('incomingQty') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="field text-xs">
                        Catatan Restok (Opsional)
                        <input wire:model="restockNotes" type="text" class="input text-xs" placeholder="Contoh: Hasil potong tambahan">
                    </label>

                    <div class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-2.5 text-[11px] leading-tight text-blue-800">
                        Menambah stok potongan ayam siap jual tanpa menambah biaya kas baru (modal telah tercatat di batch ayam hidup).
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-brand-border">
                        <button type="button" wire:click="closeRestock" class="btn text-xs py-1.5 px-3">Batal</button>
                        <button type="submit" class="btn btn-primary text-xs py-1.5 px-3" wire:loading.attr="disabled">
                            <x-icon name="check"/>Simpan Restok
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    <!-- Data Table & Filters Panel -->
    <section class="panel overflow-hidden">
        <div class="filters">
            <label class="field flex-1 min-w-44">
                Cari produk
                <input wire:model.live.debounce.300ms="search" class="input" placeholder="Cari nama produk...">
            </label>
            <label class="field">
                Satuan
                <select wire:model.live="selectedUnit" class="input">
                    <option value="">Semua satuan</option>
                    <option value="kg">Kilogram (kg)</option>
                    <option value="ekor">Ekor</option>
                </select>
            </label>
            <label class="flex min-h-10 items-center gap-2 text-xs">
                <input wire:model.live="lowStockOnly" type="checkbox">
                Hanya stok menipis
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Satuan</th>
                        <th class="text-right">Stok tersedia</th>
                        <th class="text-right">Batas minimum</th>
                        <th class="text-right">Modal rata-rata (HPP)</th>
                        <th class="text-right">Harga jual</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr wire:key="product-{{ $product->id }}">
                            <td>
                                <a href="{{ route('products.form', $product->id) }}" class="font-semibold text-brand-primary">{{ $product->name }}</a>
                            </td>
                            <td>{{ $product->unit === 'kg' ? 'Kilogram (kg)' : 'Ekor' }}</td>
                            <td class="text-right font-semibold">
                                <span @class(['text-amber-700 font-bold' => (float)$product->stock <= (float)$product->min_stock])>
                                    {{ number_format($product->stock, 2, ',', '.') }} {{ $product->unit }}
                                </span>
                            </td>
                            <td class="text-right text-brand-textGray">
                                {{ number_format($product->min_stock, 1, ',', '.') }} {{ $product->unit }}
                            </td>
                            <td class="text-right text-brand-textGray whitespace-nowrap">
                                Rp {{ number_format($product->average_cost, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-medium whitespace-nowrap">
                                Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                            </td>
                            <td>
                                <button type="button" wire:click="toggleStatus({{ $product->id }})" class="badge {{ $product->is_active ? '' : 'badge-danger' }}">
                                    {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" wire:click="openRestock({{ $product->id }})" class="btn text-xs py-1 px-2.5">
                                        Stok
                                    </button>
                                    @can('owner')
                                    <a href="{{ route('products.form', $product->id) }}" class="btn text-xs py-1 px-2.5">
                                        Detail
                                    </a>
                                    <button type="button" wire:click="deleteProduct({{ $product->id }})" wire:confirm="Apakah Anda yakin ingin menghapus produk ini dari katalog?" class="btn btn-danger text-xs py-1 px-2.5">
                                        Hapus
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">Belum ada produk yang sesuai dengan pencarian atau filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-[#eaeef2] p-5">
            {{ $products->links() }}
        </div>
    </section>
</div>
