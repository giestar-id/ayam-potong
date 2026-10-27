<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Keuangan</p>
            <h1 class="page-title">{{ $direction === 'Pengeluaran' ? 'Pengeluaran kas' : 'Pemasukan kas' }}</h1>
            <p class="page-description">Catatan kas berdasarkan tanggal uang diterima atau dibayarkan secara otomatis dan manual.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="openManualModal('{{ $direction === 'Pengeluaran' ? 'expense' : 'income' }}')" class="btn btn-primary">
                <x-icon name="plus"/> Catat {{ strtolower($direction) }} manual
            </button>
            <a class="btn" href="{{ route('cash.index', ['direction' => $direction === 'Pengeluaran' ? 'Pemasukan' : 'Pengeluaran']) }}">
                Buka {{ $direction === 'Pengeluaran' ? 'pemasukan' : 'pengeluaran' }} <x-icon name="arrow"/>
            </a>
        </div>
    </div>
    <x-feedback/>

    <!-- 4 Stat Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if($direction === 'Pengeluaran')
            <x-stat label="Total pengeluaran" :value="'Rp ' . number_format($totalExpense, 0, ',', '.')" note="Total uang kas keluar" icon="up"/>
            <x-stat label="Bahan baku & ayam hidup" :value="'Rp ' . number_format(\App\Models\CashFlow::where('type', 'expense')->where('category', 'production_cost')->sum('amount'), 0, ',', '.')" note="Pembelian ayam hidup" icon="box"/>
            <x-stat label="Upah & gaji karyawan" :value="'Rp ' . number_format(\App\Models\CashFlow::where('type', 'expense')->where('category', 'payroll')->sum('amount'), 0, ',', '.')" note="Gaji mingguan tim potong" icon="wallet"/>
            <x-stat label="Utilitas & operasional" :value="'Rp ' . number_format(\App\Models\CashFlow::where('type', 'expense')->where('category', 'operational_utility')->sum('amount'), 0, ',', '.')" note="Es batu, gas LPG, plastik" icon="up"/>
        @else
            <x-stat label="Total pemasukan" :value="'Rp ' . number_format($totalIncome, 0, ',', '.')" note="Total uang kas masuk" icon="down"/>
            <x-stat label="Penjualan kasir POS" :value="'Rp ' . number_format(\App\Models\CashFlow::where('type', 'income')->where('category', 'sales')->sum('amount'), 0, ',', '.')" note="Penerimaan langsung" icon="sales"/>
            <x-stat label="Penerimaan cicilan piutang" :value="'Rp ' . number_format(\App\Models\CashFlow::where('type', 'income')->where('category', 'debt_payment')->sum('amount'), 0, ',', '.')" note="Pelunasan tempo" icon="down"/>
            <x-stat label="Pemasukan lain-lain" :value="'Rp ' . number_format(\App\Models\CashFlow::where('type', 'income')->where('category', 'other')->sum('amount'), 0, ',', '.')" note="Modal & lain-lain" icon="wallet"/>
        @endif
    </div>

    <!-- Tabel Mutasi Kas (Full Width) -->
    <section class="panel min-w-0 overflow-hidden w-full mb-6">
            <div class="filters">
                <label class="field flex-1 min-w-40">
                    Pencarian
                    <input wire:model.live.debounce.300ms="search" class="input" placeholder="Cari keterangan atau referensi...">
                </label>
                <label class="field">
                    Kategori
                    <select wire:model.live="category" class="input">
                        <option value="">Semua kategori</option>
                        @if($direction === 'Pemasukan')
                            <option value="sales">Penjualan POS</option>
                            <option value="debt_payment">Pelunasan piutang</option>
                            <option value="other">Pemasukan lain-lain</option>
                        @else
                            <option value="stock_purchase">Belanja stok produk</option>
                            <option value="production_cost">Bahan baku & produksi</option>
                            <option value="payroll">Gaji mingguan karyawan</option>
                            <option value="operational_utility">Operasional (Listrik, gas, es, plastik)</option>
                            <option value="other">Lain-lain</option>
                        @endif
                    </select>
                </label>
                <label class="field">
                    Dari tanggal
                    <input wire:model.live="startDate" type="date" class="input">
                </label>
                <label class="field">
                    Sampai
                    <input wire:model.live="endDate" type="date" class="input">
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Keterangan transaksi</th>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th class="text-right">Nominal</th>
                            <th>Sumber</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($flows as $cf)
                            <tr wire:key="cf-{{ $cf->id }}">
                                <td class="max-w-xs">
                                    <p class="font-medium text-brand-textDark">{{ $cf->description }}</p>
                                    @if($cf->reference_number)
                                        <p class="mt-0.5 text-[10px] text-brand-textGray font-mono">Ref: {{ $cf->reference_number }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-brand-textGray">
                                    {{ $cf->transaction_date ? $cf->transaction_date->translatedFormat('d/m/Y') : '-' }}
                                </td>
                                <td>
                                    @php
                                        $catLabels = [
                                            'sales' => 'Penjualan POS',
                                            'debt_payment' => 'Pelunasan piutang',
                                            'stock_purchase' => 'Belanja stok',
                                            'production_cost' => 'Produksi ayam',
                                            'payroll' => 'Gaji karyawan',
                                            'operational_utility' => 'Operasional rutin',
                                            'other' => 'Lain-lain',
                                        ];
                                    @endphp
                                    <span class="text-xs">{{ $catLabels[$cf->category] ?? ucfirst($cf->category) }}</span>
                                </td>
                                <td class="whitespace-nowrap text-right font-semibold {{ $cf->type === 'income' ? 'text-brand-primary' : 'text-red-700' }}">
                                    {{ $cf->type === 'income' ? '+' : '-' }}Rp {{ number_format($cf->amount, 0, ',', '.') }}
                                </td>
                                <td>
                                    <span class="badge {{ $cf->reference_type ? '' : 'badge-warning' }}">
                                        {{ $cf->reference_type ? 'Otomatis' : 'Manual' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if(!$cf->reference_type)
                                        <button type="button" wire:click="deleteManualEntry({{ $cf->id }})" wire:confirm="Hapus catatan kas manual ini?" class="btn btn-danger text-xs py-1 px-2.5">
                                            Hapus
                                        </button>
                                    @else
                                        <span class="text-xs text-brand-textGray" title="Tersinkron otomatis dari modul operasional">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">Belum ada catatan arus kas yang sesuai dengan filter.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-[#eaeef2] p-5">
                {{ $flows->links() }}
            </div>
        </section>

    <!-- Modal Input Manual Kas (Muncul di Tengah dengan Background Blur) -->
    @if($showManualModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-sm sm:backdrop-blur-md"
             x-data
             @keydown.escape.window="$wire.closeManualModal()">
            <section role="dialog" aria-modal="true" class="panel relative max-h-[92dvh] overflow-y-auto p-4 shadow-2xl" style="max-width: 380px; width: 100%;">
                <div class="mb-3 flex items-start justify-between gap-2 border-b border-[#eaeef2] pb-2.5">
                    <div>
                        <p class="eyebrow text-[10px]">Catatan Kas</p>
                        <h2 class="text-sm font-bold text-brand-textDark">Catat {{ strtolower($direction) }} manual</h2>
                        <p class="mt-0.5 text-[11px] text-brand-textGray">Rincian {{ strtolower($direction) }} operasional.</p>
                    </div>
                    <button type="button" class="btn text-xs py-1 px-2" wire:click="closeManualModal" aria-label="Tutup"><x-icon name="close"/></button>
                </div>

                <form wire:submit="saveManualEntry" class="space-y-2.5 text-xs">
                    @if($direction === 'Pengeluaran')
                        <label class="field">
                            Kategori pengeluaran *
                            <select wire:model="manualCategory" class="input text-xs">
                                <option value="operational_utility">Biaya rutin (Listrik, gas, dll)</option>
                                <option value="production_cost">Biaya potong & kebersihan</option>
                                <option value="other">Pengeluaran lain-lain</option>
                            </select>
                        </label>
                    @else
                        <label class="field">
                            Kategori pemasukan *
                            <select wire:model="manualCategory" class="input text-xs">
                                <option value="other">Pemasukan lain / modal</option>
                            </select>
                        </label>
                    @endif

                    <label class="field">
                        Tanggal transaksi *
                        <input wire:model="manualDate" type="date" class="input text-xs" required>
                    </label>
                    @error('manualDate') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                    <label class="field">
                        Nominal (Rp) *
                        <input wire:model="manualAmount" type="number" step="1000" class="input font-semibold text-xs" placeholder="Contoh: 150000" required autofocus>
                    </label>
                    @error('manualAmount') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                    <label class="field">
                        Keterangan *
                        <textarea wire:model="manualDescription" rows="2" class="input text-xs" placeholder="Misal: Gas LPG 3kg, es balok" required></textarea>
                    </label>
                    @error('manualDescription') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                    <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-[#eaeef2] mt-3">
                        <button type="button" wire:click="closeManualModal" class="btn text-xs py-1.5 px-3">Batal</button>
                        <button type="submit" class="btn btn-primary text-xs py-1.5 px-3" wire:loading.attr="disabled">
                            <x-icon name="check"/>Simpan
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>
