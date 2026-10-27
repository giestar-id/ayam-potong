<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Transaksi</p>
            <h1 class="page-title">Penjualan & Piutang</h1>
            <p class="page-description">Semua nota transaksi kasir, pembayaran tunai, dan saldo piutang pelanggan.</p>
        </div>
        <a href="{{ route('sales.pos') }}" class="btn btn-primary"><x-icon name="plus"/>Transaksi kasir baru</a>
    </div>
    <x-feedback/>

    <!-- 4 Stat Cards using refrensi x-stat -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Total omzet penjualan" :value="'Rp ' . number_format($totalRevenue, 0, ',', '.')" note="Akumulasi nilai transaksi" icon="sales"/>
        <x-stat label="Jumlah transaksi nota" :value="number_format($totalSalesCount) . ' nota'" note="Seluruh transaksi kasir" icon="clock"/>
        <x-stat label="Total sudah dibayar" :value="'Rp ' . number_format($totalPaid, 0, ',', '.')" note="Penerimaan tunai & cicilan" icon="down"/>
        <x-stat label="Sisa piutang tempo" :value="'Rp ' . number_format($totalRemainingDebt, 0, ',', '.')" note="Tagihan belum tertagih" icon="wallet"/>
    </div>

    <!-- Modal Catat Pembayaran Piutang (PRD 3.5) -->
    @if($selectedSaleId)
        @php
            $selectedSale = \App\Models\Sale::with('customer')->find($selectedSaleId);
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-sm sm:backdrop-blur-md" x-data @keydown.escape.window="$wire.closeDebtPayment()">
            <section role="dialog" aria-modal="true" class="panel relative max-h-[90dvh] overflow-y-auto p-4 shadow-2xl" style="max-width: 380px; width: 100%;">
                <div class="mb-2.5 flex items-start justify-between gap-2 border-b border-brand-border pb-2">
                    <div>
                        <p class="eyebrow text-[10px]">Pelunasan piutang</p>
                        <h2 class="text-sm font-bold truncate max-w-[220px]">Bayar piutang: {{ $selectedSale->invoice_number }}</h2>
                        <p class="mt-0.5 text-[11px] text-brand-textGray">{{ $selectedSale->customer?->name ?? 'Pelanggan umum' }}</p>
                    </div>
                    <button type="button" class="btn text-xs py-1 px-2" wire:click="closeDebtPayment" aria-label="Tutup"><x-icon name="close"/></button>
                </div>

                <div class="mb-2.5 grid grid-cols-2 gap-2 rounded-lg bg-brand-bgMain p-2.5 text-xs">
                    <div>
                        <p class="text-[10px] text-brand-textGray">Total tagihan</p>
                        <p class="mt-0.5 font-semibold">Rp {{ number_format($selectedSale->grand_total, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-brand-textGray">Sisa piutang</p>
                        <p class="mt-0.5 font-semibold text-amber-700">Rp {{ number_format($selectedSale->remaining_debt, 0, ',', '.') }}</p>
                    </div>
                </div>

                <form wire:submit="saveDebtPayment" class="space-y-2.5">
                    <label class="field text-xs">
                        Nominal bayar (Rp)
                        <input wire:model="debtPayAmount" type="number" step="1000" class="input text-xs font-semibold" required>
                    </label>
                    @error('debtPayAmount') <span class="text-xs text-red-600">{{ $message }}</span> @enderror

                    <label class="field text-xs">
                        Catatan (opsional)
                        <input wire:model="debtPayNotes" type="text" class="input text-xs" placeholder="Misal: transfer / titip">
                    </label>

                    <p class="text-[10px] leading-tight text-brand-textGray">
                        Langsung mengurangi piutang & dicatat ke kas.
                    </p>

                    <div class="flex justify-end gap-2 pt-2 border-t border-brand-border">
                        <button type="button" wire:click="closeDebtPayment" class="btn text-xs py-1.5 px-3">Batal</button>
                        <button type="submit" class="btn btn-primary text-xs py-1.5 px-3" wire:loading.attr="disabled">
                            <x-icon name="check"/>Simpan
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    <!-- Data Table & Filters Panel -->
    <section class="panel overflow-hidden">
        <div class="filters">
            <label class="field flex-1 min-w-40">
                Cari nota / pelanggan
                <input wire:model.live.debounce.300ms="search" class="input" placeholder="Nomor nota atau nama pelanggan...">
            </label>
            <label class="field">
                Status pembayaran
                <select wire:model.live="status" class="input">
                    <option value="">Semua status</option>
                    <option value="paid">Lunas</option>
                    <option value="partial">Sebagian (DP)</option>
                    <option value="unpaid">Belum lunas (Piutang)</option>
                </select>
            </label>
            <label class="field">
                Dari tanggal
                <input wire:model.live="startDate" type="date" class="input">
            </label>
            <label class="field">
                Sampai tanggal
                <input wire:model.live="endDate" type="date" class="input">
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nomor nota</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th class="text-right">Total tagihan</th>
                        <th class="text-right">Dibayar</th>
                        <th class="text-right">Sisa piutang</th>
                        <th>Jatuh tempo</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}">
                            <td class="font-semibold text-brand-primary">
                                <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank">{{ $sale->invoice_number }}</a>
                            </td>
                            <td class="whitespace-nowrap text-brand-textGray">
                                {{ $sale->transaction_date ? $sale->transaction_date->translatedFormat('d/m/Y') : '-' }}
                            </td>
                            <td>{{ $sale->customer?->name ?? 'Pelanggan umum' }}</td>
                            <td class="whitespace-nowrap text-right font-medium">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap text-right text-brand-textGray">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap text-right font-semibold text-amber-700">
                                {{ (float) $sale->remaining_debt > 0 ? 'Rp ' . number_format($sale->remaining_debt, 0, ',', '.') : '-' }}
                            </td>
                            <td class="whitespace-nowrap text-brand-textGray">
                                @if($sale->due_date && $sale->payment_status !== 'paid')
                                    <span class="{{ $sale->due_date->isPast() ? 'text-red-700 font-semibold' : '' }}">
                                        {{ $sale->due_date->translatedFormat('d/m/Y') }}
                                        @if($sale->due_date->isPast())
                                            <span class="badge badge-danger ml-1">Terlambat</span>
                                        @endif
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($sale->payment_status === 'paid')
                                    <span class="badge">Lunas</span>
                                @elseif($sale->payment_status === 'partial')
                                    <span class="badge badge-warning">Sebagian (DP)</span>
                                @else
                                    <span class="badge badge-danger">Tempo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if($sale->payment_status !== 'paid')
                                        <button type="button" wire:click="openDebtPayment({{ $sale->id }})" class="btn text-xs py-1 px-2.5">
                                            Bayar piutang
                                        </button>
                                    @endif
                                    <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank" class="btn text-xs py-1 px-2.5">
                                        Lihat nota
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">Tidak ada transaksi penjualan yang sesuai dengan filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-[#eaeef2] p-5">
            {{ $sales->links() }}
        </div>
    </section>
</div>
