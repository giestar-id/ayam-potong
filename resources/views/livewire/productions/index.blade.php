<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Operasional produksi</p>
            <h1 class="page-title">Batch produksi & stok ayam</h1>
        </div>
        <a href="{{ route('productions.form') }}" class="btn btn-primary"><x-icon name="plus"/>Buat batch baru</a>
    </div>
    <x-feedback/>

    <!-- Modal Update Sisa Ayam -->
    @if($selectedBatchId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-sm sm:backdrop-blur-md" x-data @keydown.escape.window="$wire.closeUpdateRemaining()">
            <section role="dialog" aria-modal="true" class="panel relative max-h-[90dvh] overflow-y-auto p-4 shadow-2xl" style="max-width: 380px; width: 100%;">
                <div class="mb-3 flex items-start justify-between gap-2 border-b border-brand-border pb-2">
                    <div>
                        <p class="eyebrow text-[10px]">Timbang Sisa Ayam</p>
                        <h2 class="text-sm font-bold truncate max-w-[240px]">Batch: {{ $selectedBatchNumber }}</h2>
                    </div>
                    <button type="button" wire:click="closeUpdateRemaining" class="btn text-xs p-1 text-slate-500 hover:text-slate-700">
                        <x-icon name="x" class="w-4 h-4"/>
                    </button>
                </div>

                <form wire:submit="saveRemaining" class="space-y-3">
                    <div class="rounded-lg bg-slate-50 p-2.5 border border-slate-200 text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-brand-textGray">Kilo beli awal:</span>
                            <span class="font-semibold">{{ number_format($selectedBatchLiveWeight, 1, ',', '.') }} kg</span>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label for="modal_remaining_weight" class="form-label text-xs">Sisa ayam malam ini (kg) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input
                                type="number"
                                step="0.1"
                                min="0"
                                id="modal_remaining_weight"
                                wire:model="selectedBatchRemaining"
                                class="form-input text-sm @error('selectedBatchRemaining') border-red-500 @enderror"
                                placeholder="0.0"
                                required
                                autofocus
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-brand-textGray font-semibold">kg</span>
                        </div>
                        @error('selectedBatchRemaining')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-brand-border">
                        <button type="button" wire:click="closeUpdateRemaining" class="btn text-xs py-1.5 px-3">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary text-xs py-1.5 px-3">
                            Simpan Sisa
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    <!-- 2 Stat Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <x-stat label="Jumlah batch" :value="$totalBatches . ' batch'" :note="$activeBatches . ' batch berstatus aktif'" icon="box"/>
        <x-stat label="Keuntungan hari ini" :value="$todaySummary ? 'Rp ' . number_format($todaySummary['profit'], 0, ',', '.') : 'Rp 0'" :note="$todaySummary ? 'Susut: ' . number_format($todaySummary['shrinkage_kg'], 1, ',', '.') . ' kg (' . number_format($todaySummary['shrinkage_pct'], 1, ',', '.') . '%)' : 'Berdasarkan batch hari ini'" icon="wallet"/>
    </div>

    <section class="panel overflow-hidden">
        <div class="filters">
            <label class="field">
                Status batch
                <select wire:model.live="status" class="input">
                    <option value="">Semua status</option>
                    <option value="active">Aktif</option>
                    <option value="completed">Selesai</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </label>
            <p class="ml-auto text-xs text-brand-textGray self-center">
                * Update sisa ayam (kg) setiap malam untuk menghitung penyusutan dan keuntungan harian otomatis.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nomor batch</th>
                        <th>Tanggal</th>
                        <th class="text-right">Kilo beli awal</th>
                        <th class="text-right">Harga beli / kg</th>
                        <th class="text-right">Total modal</th>
                        <th class="text-right">Sisa ayam (kg)</th>
                        <th class="text-right">Nilai sisa</th>
                        <th class="text-right">Ayam keluar</th>
                        <th class="text-right">Penjualan POS</th>
                        <th class="text-right">Susut (kg & %)</th>
                        <th class="text-right">Keuntungan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        @php $pos = $batch->getPosSummary(); @endphp
                        <tr wire:key="batch-{{ $batch->id }}">
                            <td class="font-semibold text-brand-primary">
                                <a href="{{ route('productions.show', $batch->id) }}">{{ $batch->batch_number }}</a>
                            </td>
                            <td class="whitespace-nowrap text-brand-textGray text-xs">
                                {{ $batch->production_date ? $batch->production_date->translatedFormat('d/m/Y') : '-' }}
                            </td>
                            <td class="whitespace-nowrap text-right font-medium text-xs">
                                {{ number_format($batch->live_weight_kg, 1, ',', '.') }} kg
                            </td>
                            <td class="whitespace-nowrap text-right text-brand-textGray text-xs">
                                Rp {{ number_format($batch->effective_buy_price_per_kg, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap text-right font-semibold text-xs">
                                Rp {{ number_format($batch->raw_material_cost, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap text-right font-medium text-xs">
                                {{ number_format($batch->remaining_weight_kg, 1, ',', '.') }} kg
                            </td>
                            <td class="whitespace-nowrap text-right text-xs text-brand-textGray">
                                Rp {{ number_format($batch->remaining_cost, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap text-right text-xs font-medium">
                                {{ number_format($batch->used_weight_kg, 1, ',', '.') }} kg
                                <span class="block text-[10px] text-brand-textGray">Rp {{ number_format($batch->used_cost, 0, ',', '.') }}</span>
                            </td>
                            <td class="whitespace-nowrap text-right text-xs">
                                <span class="font-semibold text-brand-primary">{{ number_format($pos['sold_weight_kg'], 1, ',', '.') }} kg</span>
                                <span class="block text-[10px] text-brand-textGray">Rp {{ number_format($pos['total_revenue'], 0, ',', '.') }}</span>
                            </td>
                            <td class="whitespace-nowrap text-right text-xs">
                                <span class="font-medium text-red-700">-{{ number_format($pos['shrinkage_kg'], 1, ',', '.') }} kg</span>
                                <span class="block text-[10px] text-brand-textGray">{{ $pos['shrinkage_pct'] }}%</span>
                            </td>
                            <td class="whitespace-nowrap text-right text-xs">
                                <span class="font-bold {{ $pos['profit'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                    Rp {{ number_format($pos['profit'], 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" wire:click="openUpdateRemaining({{ $batch->id }})" class="btn text-xs py-1 px-2.5">
                                        Update sisa
                                    </button>
                                    <a href="{{ route('productions.show', $batch->id) }}" class="btn text-xs py-1 px-2.5 btn-primary">
                                        Detail batch
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12">
                                <div class="empty-state">Belum ada batch pemotongan ayam yang tercatat.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-[#eaeef2] p-5">
            {{ $batches->links() }}
        </div>
    </section>
</div>
