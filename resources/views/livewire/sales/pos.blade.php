<div x-data="{ cartOpen: false }" @keydown.escape.window="cartOpen = false" class="pb-16 xl:pb-0">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Point of sale</p>
            <h1 class="page-title">Transaksi kasir baru</h1>
            <p class="page-description">Pilih produk ayam potong, sesuaikan kuantitas desimal, lalu selesaikan pembayaran.</p>
        </div>
        <a href="{{ route('sales.index') }}" class="btn"><x-icon name="clock"/>Riwayat penjualan</a>
    </div>

    @if(session('pos_error'))
        <div role="alert" class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">{{ session('pos_error') }}</p>
        </div>
    @endif
    @if(session('pos_success'))
        <div role="status" class="mb-5 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <p class="font-semibold">{{ session('pos_success') }}</p>
        </div>
    @endif

    <!-- Mobile Drawer Backdrop -->
    <div x-show="cartOpen" x-cloak @click="cartOpen = false" class="fixed inset-0 z-30 bg-[#183247]/25 backdrop-blur-[2px] xl:hidden"></div>

    <!-- Mobile Sticky Floating Cart Button -->
    <button type="button" x-show="!menuOpen" @click="cartOpen = !cartOpen" :aria-expanded="cartOpen" aria-controls="pos-cart" class="pos-cart-toggle fixed inset-x-0 bottom-0 z-50 flex min-h-14 items-center justify-between bg-brand-primary px-5 py-3 text-sm font-semibold text-white shadow-elegant xl:hidden">
        <span class="flex items-center gap-2"><x-icon name="sales"/>Keranjang ({{ count($cart) }}) · Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
        <svg class="h-5 w-5 transition-transform" :class="cartOpen ? '' : 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div class="grid items-start gap-6 xl:grid-cols-[1fr_390px]">
        <!-- Product Catalog Section -->
        <section class="panel overflow-hidden">
            <div class="filters">
                <label class="field min-w-40 flex-1">
                    Cari produk
                    <input wire:model.live.debounce.300ms="search" class="input" placeholder="Nama atau kode produk...">
                </label>
                <label class="field">
                    Satuan
                    <select wire:model.live="selectedUnit" class="input">
                        <option value="">Semua satuan</option>
                        <option value="kg">Kilogram (kg)</option>
                        <option value="ekor">Ekor</option>
                    </select>
                </label>
            </div>

            <div class="grid grid-cols-2 gap-2.5 p-3 sm:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-4">
                @forelse($products as $product)
                    @php($inCart = isset($cart[$product->id]))
                    <button type="button" wire:key="product-{{ $product->id }}" wire:click="addToCart({{ $product->id }})" wire:loading.attr="disabled" wire:target="addToCart({{ $product->id }}),clearCart,checkout" @class(['pos-product-card group p-3 sm:p-3', 'is-selected' => $inCart]) aria-label="Tambah {{ $product->name }} ke keranjang{{ $inCart ? ' (sudah dipilih)' : '' }}">
                        <span class="mb-2.5 flex items-center justify-between gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#eef4ff] text-brand-primary">
                                <x-icon name="box" class="h-4 w-4"/>
                            </span>
                            <span class="truncate rounded-md bg-[#f0f4f8] px-2 py-1 text-[9px] font-medium text-brand-textGray">{{ $product->unit === 'kg' ? 'Kiloan' : 'Satuan' }}</span>
                        </span>
                        <span class="line-clamp-2 min-h-8 font-heading text-xs font-semibold leading-4 text-brand-textDark">{{ $product->name }}</span>
                        <span class="mt-1 truncate text-[10px] text-[#747780]">{{ $product->sku ?? '-' }}</span>
                        <span class="mt-2 flex items-start gap-1.5 text-[10px] leading-4 text-brand-textGray">
                            <span @class(['mt-1 h-1.5 w-1.5 shrink-0 rounded-full', 'bg-amber-500' => (float)$product->stock <= (float)$product->min_stock, 'bg-emerald-500' => (float)$product->stock > (float)$product->min_stock])></span>
                            Stok: {{ number_format($product->stock, 2, ',', '.') }} {{ $product->unit }}
                        </span>
                        <span class="mt-2.5 flex items-center justify-between gap-1 border-t border-[#eaeef2] pt-2.5">
                            <span class="min-w-0">
                                <span class="block font-heading text-sm font-bold tracking-tight text-brand-primary">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                                <span class="text-[9px] text-brand-textGray">per {{ $product->unit }}</span>
                            </span>
                            <span class="pos-product-add flex h-7 w-7 shrink-0 items-center justify-center rounded-lg">
                                <x-icon name="plus" class="h-4 w-4"/>
                            </span>
                        </span>
                    </button>
                @empty
                    <div class="empty-state col-span-full">
                        <x-icon name="box" class="mx-auto mb-2 h-7 w-7 text-brand-textGray"/>
                        <p>Produk dengan stok tersedia belum ditemukan.</p>
                        <a href="{{ route('products.form') }}" class="font-semibold text-brand-primary">Tambah produk baru →</a>
                    </div>
                @endforelse
            </div>
        </section>

        <!-- POS Cart Drawer & Checkout -->
        <section id="pos-cart" class="panel pos-drawer overflow-hidden" :class="cartOpen ? 'is-open' : 'is-closed'">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-[#eaeef2] bg-white p-5">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold">Keranjang transaksi</h2>
                    <p class="mt-1 text-[11px] text-brand-textGray">{{ count($cart) }} produk dipilih</p>
                </div>
                <button type="button" wire:click="clearCart" wire:loading.attr="disabled" wire:target="clearCart,checkout" @disabled(count($cart) === 0) class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-red-100 bg-red-50 px-2.5 py-2 text-[11px] font-semibold text-red-700 transition-colors hover:bg-red-100">
                    <x-icon name="trash" class="h-3.5 w-3.5"/>Kosongkan
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain pb-16 touch-pan-y xl:pb-0">
                <!-- Cart Item List -->
                <div class="space-y-4 p-5 xl:max-h-[360px] xl:overflow-y-auto">
                    @forelse($cart as $id => $item)
                        <div wire:key="cart-{{ $id }}" class="border-b-2 border-[#cbd5e1] pb-4 last:border-0">
                            <div class="flex justify-between gap-2">
                                <span class="text-sm font-medium text-brand-textDark">{{ $item['name'] }}</span>
                                <button type="button" wire:click="removeItem({{ $id }})" aria-label="Hapus {{ $item['name'] }}" class="text-brand-textGray hover:text-red-600">
                                    <x-icon name="close"/>
                                </button>
                            </div>
                            <div class="mt-3 grid grid-cols-[minmax(0,1fr)_80px_minmax(110px,1.2fr)] items-end gap-2">
                                <label class="field min-w-0 gap-1 text-[10px]">
                                    Harga / {{ $item['unit'] }}
                                    <input wire:model.blur="cart.{{ $id }}.price" wire:change="updateQty({{ $id }}, cart.{{ $id }}.qty)" inputmode="decimal" class="input min-h-9 min-w-0 px-2 py-1 text-xs">
                                </label>
                                <label class="field min-w-0 gap-1 text-[10px]">
                                    Jumlah ({{ $item['unit'] }})
                                    <input wire:model.blur="cart.{{ $id }}.qty" wire:change="updateQty({{ $id }}, $event.target.value)" inputmode="decimal" class="input min-h-9 min-w-0 px-2 py-1 text-xs font-semibold">
                                </label>
                                <div class="min-w-0">
                                    <span class="mb-1 block text-[10px] text-brand-textGray">Total</span>
                                    <span class="flex min-h-9 min-w-0 items-center justify-end overflow-hidden rounded-lg border border-[#cbd5e1] bg-[#f8fafc] px-2 text-right text-xs font-semibold tabular-nums">
                                        Rp {{ number_format($item['subtotal'] ?? 0, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <x-icon name="sales" class="mx-auto mb-3 h-8 w-8 text-[#747780]"/>
                            <p>Keranjang masih kosong.</p>
                            <p class="text-xs">Klik produk di katalog untuk memulai transaksi.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Checkout Summary & Payment Form -->
                <div class="space-y-4 border-t border-[#eaeef2] bg-[#f6fafe] p-5">
                    <div class="flex justify-between text-sm">
                        <span class="text-brand-textGray">Subtotal</span>
                        <span class="font-medium">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>

                    <!-- Diskon Pelanggan -->
                    <div class="grid grid-cols-2 gap-3">
                        <label class="field">
                            Jenis diskon
                            <select wire:model.live="discount_type" class="input">
                                <option value="none">Tidak ada</option>
                                <option value="percent">Persen (%)</option>
                                <option value="fixed">Rupiah (Rp)</option>
                            </select>
                        </label>
                        <label class="field">
                            Nilai diskon
                            <input wire:model.live.debounce.300ms="discount_value" inputmode="decimal" class="input" @disabled($discount_type === 'none') placeholder="0">
                        </label>
                    </div>

                    @if($discountAmount > 0)
                        <div class="flex justify-between text-xs text-emerald-700">
                            <span>Potongan diskon</span>
                            <span>-Rp {{ number_format($discountAmount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between border-t border-[#eaeef2] pt-3 text-lg font-bold">
                        <span>Total bayar</span>
                        <span class="text-brand-primary">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </div>

                    <!-- Pelanggan & Metode Pembayaran -->
                    <div class="grid grid-cols-2 gap-3">
                        <label class="field">
                            Pelanggan
                            <select wire:model.live="customer_id" class="input">
                                <option value="">Pelanggan umum</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="field">
                            Metode bayar
                            <select wire:model.live="payment_method" class="input">
                                <option value="cash">Tunai / Cash</option>
                                <option value="debt">Tempo / Piutang</option>
                            </select>
                        </label>
                    </div>

                    @if($payment_method === 'debt')
                        <label class="field">
                            Jatuh tempo pembayaran
                            <input type="date" wire:model.live="due_date" class="input" required>
                        </label>
                    @endif

                    <!-- Nominal Uang Diterima -->
                    <label class="field">
                        Nominal uang diterima (Rp)
                        <input wire:model.live.debounce.300ms="paid_amount" inputmode="decimal" class="input" placeholder="0">
                    </label>

                    <!-- Quick Cash Buttons -->
                    <div class="flex flex-wrap gap-2" aria-label="Nominal cepat">
                        <button type="button" wire:click="setQuickCash('exact')" class="rounded-lg border border-[#adc6ff] bg-[#eef4ff] px-3 py-2 text-[11px] font-semibold text-brand-primary hover:bg-[#d8e2ff]">
                            Uang pas
                        </button>
                        @foreach([20000, 50000, 100000, 200000, 500000] as $quickAmount)
                            <button type="button" wire:click="setQuickCash({{ $quickAmount }})" class="rounded-lg border border-[#dfe3e7] bg-white px-3 py-2 text-[11px] font-medium text-[#44474f] hover:border-[#adc6ff] hover:bg-[#f0f4f8]">
                                Rp {{ number_format($quickAmount, 0, ',', '.') }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Change / Debt calculation indicator -->
                    @if($changeAmount > 0)
                        <div class="flex justify-between text-xs font-semibold text-emerald-700 bg-emerald-50 rounded-lg p-2.5 border border-emerald-100">
                            <span>Kembalian</span>
                            <span>Rp {{ number_format($changeAmount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    @if($remainingDebt > 0)
                        <div class="flex justify-between text-xs font-semibold text-amber-700 bg-amber-50 rounded-lg p-2.5 border border-amber-100">
                            <span>Sisa piutang (Tempo)</span>
                            <span>Rp {{ number_format($remainingDebt, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <label class="field">
                        Catatan transaksi (opsional)
                        <input wire:model="notes" class="input" placeholder="Keterangan transaksi...">
                    </label>

                    <button type="button" wire:click="checkout" wire:loading.attr="disabled" class="btn btn-primary w-full">
                        <x-icon name="check"/>
                        <span wire:loading.remove wire:target="checkout">Konfirmasi transaksi & cetak nota</span>
                        <span wire:loading wire:target="checkout">Menyimpan transaksi…</span>
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>
