<div>
    <div class="page-heading">
        <div>
            <div class="flex items-center gap-3">
                <span class="font-mono text-xl font-bold text-brand-primary">{{ $batch->batch_number }}</span>
                @if($batch->status === 'active')
                    <span class="badge badge-warning">Aktif</span>
                @elseif($batch->status === 'completed')
                    <span class="badge">Selesai</span>
                @else
                    <span class="badge badge-danger">Batal</span>
                @endif
            </div>
            <p class="text-xs text-brand-textGray mt-1">Tanggal produksi: {{ $batch->production_date ? $batch->production_date->translatedFormat('d F Y') : '-' }}</p>
        </div>
        <a href="{{ route('productions.index') }}" class="btn">Kembali ke daftar batch</a>
    </div>
    <x-feedback/>

    <!-- 4 Kartu Ringkasan Finansial & Timbangan Batch -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <!-- Card 1: Modal Beli Awal -->
        <div class="panel p-5 min-w-0 overflow-hidden flex flex-col justify-between">
            <span class="text-xs font-semibold text-brand-textDark truncate">Modal Beli Awal</span>
            <div class="my-2">
                <p class="card-fit-value text-brand-textDark font-bold" title="Rp {{ number_format($batch->raw_material_cost, 0, ',', '.') }}">Rp {{ number_format($batch->raw_material_cost, 0, ',', '.') }}</p>
                <p class="text-xs font-medium text-brand-textGray mt-0.5 truncate" title="{{ number_format($batch->live_weight_kg, 1, ',', '.') }} kg">
                    {{ number_format($batch->live_weight_kg, 1, ',', '.') }} kg (Rp {{ number_format($batch->effective_buy_price_per_kg, 0, ',', '.') }}/kg)
                </p>
            </div>
        </div>

        <!-- Card 2: Sisa Malam -->
        <div class="panel p-5 min-w-0 overflow-hidden flex flex-col justify-between">
            <span class="text-xs font-semibold text-brand-textDark truncate">Sisa Malam</span>
            <div class="my-2">
                <p class="card-fit-value text-brand-textDark font-bold" title="Rp {{ number_format($posSummary['remaining_cost'], 0, ',', '.') }}">Rp {{ number_format($posSummary['remaining_cost'], 0, ',', '.') }}</p>
                <p class="text-xs font-medium text-brand-textGray mt-0.5 truncate" title="{{ number_format($batch->remaining_weight_kg, 1, ',', '.') }} kg">
                    {{ number_format($batch->remaining_weight_kg, 1, ',', '.') }} kg
                </p>
            </div>
        </div>

        <!-- Card 3: Ayam Keluar -->
        <div class="panel p-5 min-w-0 overflow-hidden flex flex-col justify-between">
            <span class="text-xs font-semibold text-brand-textDark truncate">Ayam Keluar</span>
            <div class="my-2">
                <p class="card-fit-value text-brand-textDark font-bold" title="Rp {{ number_format($posSummary['used_cost'], 0, ',', '.') }}">Rp {{ number_format($posSummary['used_cost'], 0, ',', '.') }}</p>
                <p class="text-xs font-medium text-brand-textGray mt-0.5 truncate" title="{{ number_format($posSummary['used_weight_kg'], 1, ',', '.') }} kg">
                    {{ number_format($posSummary['used_weight_kg'], 1, ',', '.') }} kg
                </p>
            </div>
        </div>

        <!-- Card 4: Penjualan Kasir POS -->
        <div class="panel p-5 min-w-0 overflow-hidden flex flex-col justify-between">
            <span class="text-xs font-semibold text-brand-textDark truncate">Penjualan Kasir (POS)</span>
            <div class="my-2">
                <p class="card-fit-value text-brand-textDark font-bold" title="Rp {{ number_format($posSummary['total_revenue'], 0, ',', '.') }}">Rp {{ number_format($posSummary['total_revenue'], 0, ',', '.') }}</p>
                <p class="text-xs font-medium text-brand-textGray mt-0.5 truncate" title="{{ number_format($posSummary['sold_weight_kg'], 1, ',', '.') }} kg">
                    {{ number_format($posSummary['sold_weight_kg'], 1, ',', '.') }} kg ({{ $posSummary['sales_count'] }} transaksi)
                </p>
            </div>
        </div>
    </div>

    <!-- 2 Highlight Cards: Susut Bobot & Keuntungan -->
    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <!-- Highlight 1: Penyusutan Bobot -->
        <section class="panel p-5 border-l-4 border-l-amber-500 min-w-0 overflow-hidden">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-bold text-brand-textDark truncate">Penyusutan Bobot</h2>
                <span class="rounded-lg bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800 shrink-0">
                    Susut {{ $posSummary['shrinkage_pct'] }}%
                </span>
            </div>
            <div class="my-3 flex items-baseline gap-2 min-w-0">
                <span class="card-fit-value-lg font-bold text-amber-700" title="-{{ number_format($posSummary['shrinkage_kg'], 1, ',', '.') }} kg">-{{ number_format($posSummary['shrinkage_kg'], 1, ',', '.') }} kg</span>
            </div>
            <div class="rounded-lg bg-[#f8fafc] p-3 text-xs space-y-1.5 border border-[#e2e8f0]">
                <div class="flex justify-between">
                    <span class="text-brand-textGray">Ayam kotor keluar:</span>
                    <span class="font-medium text-brand-textDark">{{ number_format($posSummary['used_weight_kg'], 1, ',', '.') }} kg</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-brand-textGray">Daging terjual kasir:</span>
                    <span class="font-medium text-brand-textDark">{{ number_format($posSummary['sold_weight_kg'], 1, ',', '.') }} kg</span>
                </div>
            </div>
        </section>

        <!-- Highlight 2: Keuntungan Hari Ini -->
        <section class="panel p-5 border-l-4 {{ $posSummary['profit'] >= 0 ? 'border-l-emerald-500' : 'border-l-red-500' }} min-w-0 overflow-hidden">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-bold text-brand-textDark truncate">Keuntungan Hari Ini</h2>
                <span class="rounded-lg {{ $posSummary['profit'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }} px-2.5 py-0.5 text-xs font-bold shrink-0">
                    Margin {{ $posSummary['profit_margin'] }}%
                </span>
            </div>
            <div class="my-3 flex items-baseline gap-2 min-w-0">
                <span class="card-fit-value-lg font-bold {{ $posSummary['profit'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}" title="Rp {{ number_format($posSummary['profit'], 0, ',', '.') }}">
                    Rp {{ number_format($posSummary['profit'], 0, ',', '.') }}
                </span>
            </div>
            <div class="rounded-lg bg-[#f8fafc] p-3 text-xs space-y-1.5 border border-[#e2e8f0]">
                <div class="flex justify-between">
                    <span class="text-brand-textGray">Omzet penjualan kasir:</span>
                    <span class="font-medium text-brand-textDark">Rp {{ number_format($posSummary['total_revenue'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-brand-textGray">Modal kotor ayam keluar:</span>
                    <span class="font-medium text-brand-textDark">-Rp {{ number_format($posSummary['used_cost'], 0, ',', '.') }}</span>
                </div>
            </div>
        </section>
    </div>

    <!-- Section Form Update Sisa Ayam Malam Hari (Simpel, Bersih, Tanpa Tabel Ribet) -->
    <section class="panel p-6 bg-[#fbfcfe] border border-blue-200 max-w-xl">
        <h2 class="text-sm font-bold text-brand-primary">Update Sisa Ayam Malam Hari</h2>
        <form wire:submit="updateRemaining" class="mt-4 space-y-4">
            <label class="field">
                Sisa ayam (kg) *
                <div class="flex items-center gap-2">
                    <input wire:model="remaining_weight_kg" type="number" step="0.1" min="0" class="input font-bold text-base text-brand-primary" placeholder="0.0" required>
                    <span class="text-sm font-semibold text-brand-textGray">kg</span>
                </div>
            </label>

            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <x-icon name="check"/>Simpan Sisa Malam
            </button>
        </form>
    </section>
</div>
