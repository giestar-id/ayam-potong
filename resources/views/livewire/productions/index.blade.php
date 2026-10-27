<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Operasional produksi</p>
            <h1 class="page-title">Batch pemotongan ayam</h1>
            <p class="page-description">Kelola siklus transformasi ayam hidup menjadi potongan karkas siap jual, pantau penyusutan bobot, dan alokasi HPP.</p>
        </div>
        <a href="{{ route('productions.form') }}" class="btn btn-primary"><x-icon name="plus"/>Buat batch pemotongan baru</a>
    </div>
    <x-feedback/>

    <!-- 4 Stat Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Total batch pemotongan" :value="number_format($totalBatches) . ' batch'" note="Seluruh siklus tercatat" icon="leaf"/>
        <x-stat label="Sedang dalam proses" :value="number_format($activeBatches) . ' batch'" note="Menunggu penimbangan akhir" icon="clock"/>
        <x-stat label="Total ayam hidup dipotong" :value="number_format($totalLiveWeight, 1, ',', '.') . ' kg'" note="Kumulatif berat bahan baku" icon="box"/>
        <x-stat label="Hasil bersih karkas" :value="number_format($totalOutputWeight, 1, ',', '.') . ' kg'" note="Total potongan masuk stok" icon="box"/>
    </div>

    <section class="panel overflow-hidden">
        <div class="filters">
            <label class="field">
                Status batch
                <select wire:model.live="status" class="input">
                    <option value="">Semua status</option>
                    <option value="active">Sedang diproses (Aktif)</option>
                    <option value="completed">Selesai (Masuk stok)</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nomor batch</th>
                        <th>Tanggal</th>
                        <th class="text-right">Berat hidup</th>
                        <th class="text-right">Modal ayam hidup</th>
                        <th class="text-right">Biaya operasional</th>
                        <th class="text-right">Hasil karkas</th>
                        <th class="text-right">Susut (kg & %)</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr wire:key="batch-{{ $batch->id }}">
                            <td class="font-semibold text-brand-primary">
                                <a href="{{ route('productions.show', $batch->id) }}">{{ $batch->batch_number }}</a>
                            </td>
                            <td class="whitespace-nowrap text-brand-textGray">
                                {{ $batch->production_date ? $batch->production_date->translatedFormat('d/m/Y') : '-' }}
                            </td>
                            <td class="whitespace-nowrap text-right font-medium">
                                {{ number_format($batch->live_weight_kg, 1, ',', '.') }} kg
                                @if($batch->live_bird_count)
                                    <span class="block text-[10px] text-brand-textGray">({{ $batch->live_bird_count }} ekor)</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right text-brand-textGray">Rp {{ number_format($batch->raw_material_cost, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap text-right text-brand-textGray">Rp {{ number_format($batch->additional_cost, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap text-right font-semibold text-brand-primary">
                                {{ $batch->status === 'completed' ? number_format($batch->total_output_weight_kg, 2, ',', '.') . ' kg' : '-' }}
                            </td>
                            <td class="whitespace-nowrap text-right">
                                @if($batch->status === 'completed')
                                    <span class="font-medium text-red-700">-{{ number_format($batch->shrinkage_kg, 1, ',', '.') }} kg</span>
                                    <span class="block text-[10px] text-brand-textGray">Yield: {{ $batch->yield_percentage }}%</span>
                                @else
                                    <span class="text-brand-textGray">-</span>
                                @endif
                            </td>
                            <td>
                                @if($batch->status === 'active')
                                    <span class="badge badge-warning">Dalam proses</span>
                                @elseif($batch->status === 'completed')
                                    <span class="badge">Selesai</span>
                                @else
                                    <span class="badge badge-danger">Batal</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('productions.show', $batch->id) }}" class="btn text-xs py-1 px-3 {{ $batch->status === 'active' ? 'btn-primary' : '' }}">
                                    {{ $batch->status === 'active' ? 'Timbang karkas' : 'Detail batch' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
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
