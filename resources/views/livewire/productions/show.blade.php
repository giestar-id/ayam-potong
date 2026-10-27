<div>
    <div class="page-heading">
        <div>
            <div class="flex items-center gap-3">
                <span class="font-mono text-lg font-bold text-brand-primary">{{ $batch->batch_number }}</span>
                @if($batch->status === 'active')
                    <span class="badge badge-warning">Dalam proses</span>
                @elseif($batch->status === 'completed')
                    <span class="badge">Selesai (Sudah masuk stok)</span>
                @else
                    <span class="badge badge-danger">Batal</span>
                @endif
            </div>
            <p class="page-description mt-1">Tanggal: {{ $batch->production_date ? $batch->production_date->translatedFormat('d F Y') : '-' }} · Input hasil timbang, pantau rendemen/susut, dan sinkronisasi ke stok.</p>
        </div>
        <a href="{{ route('productions.index') }}" class="btn">Kembali ke daftar batch</a>
    </div>
    <x-feedback/>

    <!-- 4 Metrik Batch -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Berat ayam hidup (kotor)" :value="number_format($batch->live_weight_kg, 1, ',', '.') . ' kg'" :note="$batch->live_bird_count ? $batch->live_bird_count . ' ekor' : 'Tanpa data ekor'" icon="box"/>
        <x-stat label="Hasil timbang bersih" :value="($batch->status === 'completed' ? number_format($batch->total_output_weight_kg, 2, ',', '.') : number_format($totalCleanWeight, 2, ',', '.')) . ' kg'" :note="'Rendemen: ' . ($batch->status === 'completed' ? $batch->yield_percentage : $yieldPct) . '%'" icon="leaf"/>
        <x-stat label="Penyusutan bobot (susut)" :value="($batch->status === 'completed' ? number_format($batch->shrinkage_kg, 2, ',', '.') : number_format($shrinkageKg, 2, ',', '.')) . ' kg'" :note="'Susut: ' . ($batch->status === 'completed' ? round((($batch->shrinkage_kg / max(1, $batch->live_weight_kg)) * 100), 2) : $shrinkagePct) . '%'" icon="clock"/>
        <x-stat label="Total modal batch" :value="'Rp ' . number_format($totalBatchCost, 0, ',', '.')" :note="'HPP karkas: Rp ' . number_format($batchHppPerKg, 0, ',', '.') . ' / kg'" icon="wallet"/>
    </div>

    @if($batch->status === 'active')
        <!-- Formulir Input Timbang Hasil Potongan (PRD 3.2) -->
        <div class="grid items-start gap-6 lg:grid-cols-12 mb-8">
            <!-- Kolom Kiri: Input Tabel Potongan (8 Kolom) -->
            <section class="panel p-6 lg:col-span-8">
                <div class="mb-4">
                    <h2 class="text-base font-semibold">Input hasil timbangan potongan ayam</h2>
                    <p class="mt-1 text-xs text-brand-textGray">Masukkan rincian berat (kg) atau jumlah (ekor) untuk setiap produk yang dihasilkan dari batch ini.</p>
                </div>

                <div class="divide-y divide-[#eaeef2]">
                    @foreach($breakdown as $index => $item)
                        <div class="flex items-center justify-between py-3">
                            <div class="min-w-0 pr-3">
                                <p class="text-xs font-semibold text-brand-textDark">{{ $item['product_name'] }}</p>
                                <span class="rounded bg-[#f0f4f8] px-2 py-0.5 text-[10px] text-brand-textGray">Satuan: {{ $item['unit'] }}</span>
                            </div>
                            <div class="w-44">
                                <div class="flex items-center gap-1.5">
                                    <input wire:model.live.debounce.300ms="breakdown.{{ $index }}.output_qty" type="number" step="0.01" class="input text-right font-semibold text-sm" placeholder="0">
                                    <span class="text-xs font-semibold text-brand-textGray w-10 text-right">{{ $item['unit'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Kolom Kanan: Rangkuman & Selesaikan (4 Kolom) -->
            <div class="space-y-6 lg:col-span-4">
                <!-- Biaya Tambahan Operasional -->
                <section class="panel p-5 space-y-4">
                    <h3 class="text-sm font-semibold">Biaya operasional batch</h3>
                    <div>
                        <span class="text-xs text-brand-textGray">Modal beli ayam hidup:</span>
                        <p class="text-sm font-semibold text-brand-textDark">Rp {{ number_format($batch->raw_material_cost, 0, ',', '.') }}</p>
                    </div>

                    <label class="field">
                        Biaya operasional tambahan (Rp)
                        <input wire:model.live.debounce.300ms="additional_cost" type="number" step="1000" class="input font-semibold" placeholder="0">
                    </label>
                    <span class="text-[11px] leading-5 text-brand-textGray">Contoh: biaya es batu, kantong plastik, atau upah potong harian batch ini.</span>

                    <label class="field">
                        Catatan batch
                        <textarea wire:model="notes" rows="2" class="input" placeholder="Catatan supplier, berat susut dll."></textarea>
                    </label>

                    <button wire:click="updateAdditionalCost" type="button" class="btn w-full text-xs">Simpan catatan & biaya</button>
                </section>

                <!-- Box Selesaikan Batch -->
                <section class="panel p-5 bg-[#f6fafe] border border-[#d8e2ff] space-y-4">
                    <h3 class="text-sm font-semibold text-brand-primary">Rangkuman batch</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-brand-textGray">Berat hidup:</span>
                            <span class="font-semibold">{{ number_format($batch->live_weight_kg, 1, ',', '.') }} kg</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-brand-textGray">Hasil timbang bersih:</span>
                            <span class="font-semibold text-brand-primary">{{ number_format($totalCleanWeight, 2, ',', '.') }} kg</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-brand-textGray">Susut bobot:</span>
                            <span class="font-semibold text-red-700">-{{ number_format($shrinkageKg, 2, ',', '.') }} kg ({{ $shrinkagePct }}%)</span>
                        </div>
                        <div class="flex justify-between border-t border-blue-200 pt-2 font-bold text-brand-primary">
                            <span>Estimasi HPP rata-rata:</span>
                            <span>Rp {{ number_format($batchHppPerKg, 0, ',', '.') }} / kg</span>
                        </div>
                    </div>

                    <p class="text-[11px] leading-5 text-brand-textGray">
                        Ketika diselesaikan: seluruh hasil timbangan akan ditambahkan ke stok produk, HPP produk diperbarui secara moving average, dan biaya dicatat otomatis ke menu Pengeluaran.
                    </p>

                    <button wire:click="completeProduction" wire:confirm="Apakah Anda yakin seluruh timbangan sudah benar? Setelah diselesaikan, batch tidak dapat diedit ulang." class="btn btn-primary w-full">
                        <x-icon name="check"/>Selesaikan & masukkan ke stok
                    </button>
                </section>
            </div>
        </div>
    @else
        <!-- Tampilan Batch yang Sudah Selesai (Locked) -->
        <section class="panel overflow-hidden mb-8">
            <div class="border-b border-[#eaeef2] p-5 bg-[#f0f9ff]">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-brand-primary">Rincian hasil pemotongan (Selesai)</h2>
                        <p class="mt-1 text-xs text-brand-textGray">Seluruh hasil timbangan telah ditambahkan ke persediaan produk dan biaya tercatat di arus kas.</p>
                    </div>
                    <span class="badge">Selesai & terkunci</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama potongan produk</th>
                            <th>Satuan</th>
                            <th class="text-right">Hasil timbangan</th>
                            <th class="text-right">Alokasi HPP per satuan</th>
                            <th class="text-right">Total nilai modal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batch->outputs as $out)
                            <tr>
                                <td class="font-semibold text-brand-textDark">{{ $out->product->name }}</td>
                                <td><span class="rounded bg-[#f0f4f8] px-2 py-0.5 text-xs">{{ $out->product->unit }}</span></td>
                                <td class="text-right font-semibold text-brand-primary">{{ number_format($out->output_qty, 2, ',', '.') }} {{ $out->product->unit }}</td>
                                <td class="text-right text-brand-textGray">Rp {{ number_format($out->allocated_cost_per_unit, 0, ',', '.') }}</td>
                                <td class="text-right font-medium">Rp {{ number_format($out->output_qty * $out->allocated_cost_per_unit, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-[#f0f4f8] font-bold border-t border-[#dfe3e7]">
                            <td colspan="2">TOTAL HASIL BERSIH</td>
                            <td class="text-right text-brand-primary">{{ number_format($batch->total_output_weight_kg, 2, ',', '.') }} kg</td>
                            <td class="text-right">Rp {{ number_format($batch->cost_per_kg, 0, ',', '.') }} / kg</td>
                            <td class="text-right">Rp {{ number_format($batch->total_cost, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    @endif
</div>
