<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Mitra & pembeli</p>
            <h1 class="page-title">Pelanggan</h1>
            <p class="page-description">Kelola pelanggan tetap, tingkat diskon otomatis kasir, dan pantau saldo piutang aktif.</p>
        </div>
        <button type="button" wire:click="openCreate" class="btn btn-primary">
            <x-icon name="plus"/> Tambah pelanggan baru
        </button>
    </div>
    <x-feedback/>

    <!-- 4 Stat Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Total pelanggan" :value="number_format($customers->total()) . ' orang'" note="Mitra & warung terdaftar" icon="users"/>
        <x-stat label="Total piutang berjalan" :value="'Rp ' . number_format($totalActiveDebt, 0, ',', '.')" note="Saldo tagihan aktif" icon="wallet"/>
        <x-stat label="Pelanggan berdiskon" :value="number_format(\App\Models\Customer::where('discount_type', '!=', 'none')->count()) . ' mitra'" note="Mendapat diskon otomatis" icon="box"/>
        <x-stat label="Transaksi kasir" :value="number_format(\App\Models\Sale::whereNotNull('customer_id')->count()) . ' nota'" note="Belanja atas nama pelanggan" icon="sales"/>
    </div>

    <!-- Tabel Daftar Pelanggan (Full Width) -->
    <section class="panel min-w-0 overflow-hidden w-full mb-6">
            <div class="filters">
                <label class="field flex-1 min-w-44">
                    Cari pelanggan
                    <input wire:model.live.debounce.300ms="search" class="input" placeholder="Cari nama atau nomor telepon...">
                </label>
                <div class="text-xs text-brand-textGray pt-5">
                    Total piutang aktif: <strong class="text-amber-700">Rp {{ number_format($totalActiveDebt, 0, ',', '.') }}</strong>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama pelanggan</th>
                            <th>Telepon</th>
                            <th>Diskon bawaan</th>
                            <th class="text-right">Total belanja</th>
                            <th class="text-right">Piutang aktif</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $c)
                            <tr wire:key="cust-{{ $c->id }}">
                                <td>
                                    <button type="button" wire:click="viewDetails({{ $c->id }})" class="font-semibold text-brand-primary text-left">
                                        {{ $c->name }}
                                    </button>
                                    @if($c->notes)
                                        <p class="mt-1 text-[10px] text-brand-textGray">{{ $c->notes }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-brand-textGray">{{ $c->phone ?? '-' }}</td>
                                <td>
                                    @if($c->discount_type === 'percent')
                                        <span class="badge">{{ $c->discount_value }}%</span>
                                    @elseif($c->discount_type === 'fixed')
                                        <span class="badge">Rp {{ number_format($c->discount_value, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-xs text-brand-textGray">-</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-right font-medium">Rp {{ number_format($c->total_purchases, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap text-right font-semibold {{ $c->active_debt > 0 ? 'text-amber-700' : 'text-brand-textGray' }}">
                                    {{ $c->active_debt > 0 ? 'Rp ' . number_format($c->active_debt, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" wire:click="viewDetails({{ $c->id }})" class="btn text-xs py-1 px-2.5">
                                            Riwayat
                                        </button>
                                        <button type="button" wire:click="edit({{ $c->id }})" class="btn text-xs py-1 px-2.5">
                                            Edit
                                        </button>
                                        <button type="button" wire:click="delete({{ $c->id }})" wire:confirm="Hapus pelanggan ini?" class="btn btn-danger text-xs py-1 px-2.5">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">Belum ada data pelanggan yang tercatat.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-[#eaeef2] p-5">
                {{ $customers->links() }}
            </div>
        </section>

    <!-- Modal Form Tambah/Ubah Pelanggan (Muncul di Tengah dengan Background Blur) -->
    @if($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-sm sm:backdrop-blur-md"
             x-data
             @keydown.escape.window="$wire.closeFormModal()">
            <section role="dialog" aria-modal="true" class="panel relative max-h-[92dvh] overflow-y-auto p-4 shadow-2xl" style="max-width: 380px; width: 100%;">
                <div class="mb-3 flex items-start justify-between gap-2 border-b border-[#eaeef2] pb-2.5">
                    <div>
                        <p class="eyebrow text-[10px]">Data Mitra</p>
                        <h2 class="text-sm font-bold text-brand-textDark">{{ $customerId ? 'Ubah data pelanggan' : 'Tambah pelanggan baru' }}</h2>
                        <p class="mt-0.5 text-[11px] text-brand-textGray">Lengkapi identitas pelanggan & diskon.</p>
                    </div>
                    <button type="button" class="btn text-xs py-1 px-2" wire:click="closeFormModal" aria-label="Tutup"><x-icon name="close"/></button>
                </div>

                <form wire:submit="save" class="space-y-2.5 text-xs">
                    <label class="field">
                        Nama pelanggan *
                        <input wire:model="name" class="input text-xs" placeholder="Nama orang / warung" required autofocus>
                    </label>
                    @error('name') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                    <label class="field">
                        Nomor WhatsApp / telepon
                        <input wire:model="phone" class="input text-xs" placeholder="08xxxxxxxxxx">
                    </label>
                    @error('phone') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                    <label class="field">
                        Alamat lengkap
                        <textarea wire:model="address" rows="2" class="input text-xs" placeholder="Alamat pengiriman"></textarea>
                    </label>

                    <div class="grid grid-cols-2 gap-2">
                        <label class="field">
                            Tipe diskon
                            <select wire:model.live="discount_type" class="input text-xs">
                                <option value="none">Tanpa diskon</option>
                                <option value="percent">Persen (%)</option>
                                <option value="fixed">Nominal (Rp)</option>
                            </select>
                        </label>
                        <label class="field">
                            Nilai diskon
                            <input wire:model="discount_value" type="number" step="0.5" class="input text-xs" {{ $discount_type === 'none' ? 'readonly' : '' }}>
                        </label>
                    </div>

                    <label class="field">
                        Catatan khusus (opsional)
                        <input wire:model="notes" class="input text-xs" placeholder="Catatan pesanan / tempo">
                    </label>

                    <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-[#eaeef2] mt-3">
                        <button type="button" wire:click="closeFormModal" class="btn text-xs py-1.5 px-3">Batal</button>
                        <button type="submit" class="btn btn-primary text-xs py-1.5 px-3" wire:loading.attr="disabled">
                            <x-icon name="check"/>{{ $customerId ? 'Simpan' : 'Tambah' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    <!-- Modal Riwayat Transaksi Pelanggan (PRD 3.6) -->
    @if($detailCustomer)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm" x-data @keydown.escape.window="$wire.closeDetails()">
            <section role="dialog" aria-modal="true" class="panel relative max-h-[90dvh] w-full max-w-2xl overflow-y-auto p-6 shadow-2xl">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="eyebrow">Riwayat transaksi</p>
                        <h2 class="text-xl font-bold">{{ $detailCustomer->name }}</h2>
                        <p class="mt-1 text-sm text-brand-textGray">
                            Total belanja: <strong>Rp {{ number_format($detailCustomer->total_purchases, 0, ',', '.') }}</strong> · Sisa piutang: <strong class="text-amber-700">Rp {{ number_format($detailCustomer->active_debt, 0, ',', '.') }}</strong>
                        </p>
                    </div>
                    <button type="button" class="btn" wire:click="closeDetails" aria-label="Tutup"><x-icon name="close"/></button>
                </div>

                <div class="space-y-3">
                    @forelse($detailCustomer->sales as $sale)
                        <div class="flex items-center justify-between rounded-xl border border-[#eaeef2] bg-brand-bgMain p-3.5 text-xs">
                            <div>
                                <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank" class="font-semibold text-brand-primary">{{ $sale->invoice_number }}</a>
                                <span class="ml-2 text-brand-textGray">{{ $sale->transaction_date ? $sale->transaction_date->translatedFormat('d/m/Y') : '-' }}</span>
                                <p class="mt-1 text-[11px] text-brand-textGray">
                                    {{ $sale->items->pluck('product.name')->join(', ') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-brand-textDark">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</p>
                                @if($sale->payment_status === 'paid')
                                    <span class="badge text-[10px]">Lunas</span>
                                @else
                                    <span class="badge badge-warning text-[10px]">Sisa: Rp {{ number_format($sale->remaining_debt, 0, ',', '.') }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state py-6">Pelanggan ini belum memiliki riwayat pembelian.</div>
                    @endforelse
                </div>

                <div class="flex justify-end pt-4 border-t border-[#eaeef2] mt-4">
                    <button type="button" wire:click="closeDetails" class="btn">Tutup</button>
                </div>
            </section>
        </div>
    @endif
</div>
