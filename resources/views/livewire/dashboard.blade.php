<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Ringkasan operasional</p>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-description">Ringkasan produksi ayam potong, penjualan kasir, dan keuangan usaha Anda.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sales.index') }}" class="btn"><x-icon name="clock"/>Riwayat penjualan</a>
            <a href="{{ route('sales.pos') }}" class="btn btn-primary"><x-icon name="plus"/>Transaksi kasir</a>
        </div>
    </div>
    <x-feedback/>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex rounded-xl bg-white p-1 shadow-soft">
            @foreach(['today' => 'Hari ini', 'yesterday' => 'Kemarin', 'week' => 'Minggu ini', 'month' => 'Bulan ini'] as $value => $label)
                <button wire:click="$set('period', '{{ $value }}')" @class(['period-tab', 'is-active' => $period === $value])>{{ $label }}</button>
            @endforeach
        </div>
        <div class="flex min-w-0 items-center gap-2 text-xs text-brand-textGray">
            <x-icon name="calendar"/>{{ \Carbon\Carbon::parse($from)->locale('id')->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($to)->locale('id')->translatedFormat('d M Y') }}
        </div>
    </div>

    <!-- 4 Summary Stats -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Total pemasukan" :value="'Rp ' . number_format($summary['total_income'], 0, ',', '.')" note="Penjualan tunai & pelunasan piutang" icon="down"/>
        <x-stat label="Total pengeluaran" :value="'Rp ' . number_format($summary['total_expense'], 0, ',', '.')" note="Bahan baku ayam hidup, gaji & operasional" icon="up"/>
        <x-stat label="Arus kas bersih" :value="'Rp ' . number_format($summary['net_cash'], 0, ',', '.')" note="Pemasukan kas dikurangi pengeluaran" icon="wallet"/>
        <x-stat label="Piutang aktif" :value="'Rp ' . number_format($summary['total_debt'], 0, ',', '.')" note="Tagihan tempo yang belum dilunasi" icon="clock"/>
    </div>

    <!-- Calculations for SVG Line Chart & Donut Composition -->
    @php
        $cashIn = (float) $summary['total_income'];
        $cashOut = (float) $summary['total_expense'];
        $cashTotal = $cashIn + $cashOut;
        $inPercent = $cashTotal > 0 ? round(($cashIn / $cashTotal) * 100, 1) : 0;
        $outPercent = $cashTotal > 0 ? round(100 - $inPercent, 1) : 0;

        $plotDays = $chart['days'] ?? [];
        if (empty($plotDays)) {
            for ($i = 6; $i >= 0; $i--) {
                $d = now()->subDays($i)->toDateString();
                $plotDays[] = [
                    'date' => $d,
                    'label' => date('d M', strtotime($d)),
                    'in' => 0.0,
                    'out' => 0.0,
                ];
            }
        }

        $allIn = array_column($plotDays, 'in');
        $allOut = array_column($plotDays, 'out');
        $chartMax = max(1, ...$allIn, ...$allOut);
        $plotDenominator = max(count($plotDays) - 1, 1);
        $labelStep = max(1, (int) ceil(count($plotDays) / 7));
        $inPoints = [];
        $outPoints = [];
        foreach ($plotDays as $index => $day) {
            $x = round(42 + $index / $plotDenominator * 570, 2);
            $inPoints[] = $x . ' ' . round(180 - $day['in'] / $chartMax * 145, 2);
            $outPoints[] = $x . ' ' . round(180 - $day['out'] / $chartMax * 145, 2);
        }
        if (count($plotDays) === 1) {
            $inPoints[] = '612 ' . round(180 - $plotDays[0]['in'] / $chartMax * 145, 2);
            $outPoints[] = '612 ' . round(180 - $plotDays[0]['out'] / $chartMax * 145, 2);
        }

        $maxTopQty = max(1, ...array_column($topProducts, 'total_qty'));
    @endphp

    <!-- Baris 1: Grafik Garis Arus Kas 7 Hari (Persis Refrensi) & Komposisi Kas Donut -->
    <div class="mb-6 grid gap-4 lg:grid-cols-12">
        <!-- Grafik Garis Arus Kas (Line & Area Chart Sesuai Refrensi) -->
        <section class="panel min-w-0 p-6 lg:col-span-7" aria-labelledby="cash-chart-title">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="cash-chart-title" class="text-base font-semibold">Arus kas 7 hari terakhir</h2>
                    <p class="mt-1 text-xs text-brand-textGray">Penerimaan dan pembayaran aktual · Rupiah</p>
                </div>
                <div class="flex gap-3 text-[11px] text-brand-textGray">
                    <span class="flex items-center gap-1.5"><span class="chart-legend-dot bg-brand-primary"></span>Pemasukan</span>
                    <span class="flex items-center gap-1.5"><span class="chart-legend-dot bg-chart-accent"></span>Pengeluaran</span>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto">
                <svg class="block h-auto min-w-[360px] w-full" viewBox="0 0 640 224" role="img" aria-label="Grafik arus kas harian">
                    <defs>
                        <linearGradient id="cash-out-fill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#f67113" stop-opacity=".2"/>
                            <stop offset="100%" stop-color="#f67113" stop-opacity="0"/>
                        </linearGradient>
                    </defs>

                    <!-- Garis batas putus-putus dan label nominal -->
                    @foreach(range(0, 3) as $line)
                        <line x1="42" x2="612" y1="{{ 35 + $line * 145 / 3 }}" y2="{{ 35 + $line * 145 / 3 }}" stroke="#dfe3e7" stroke-width="1" stroke-dasharray="3 4"/>
                        <text x="34" y="{{ 38 + $line * 145 / 3 }}" text-anchor="end" fill="#747780" font-size="9">
                            {{ $chartMax >= 1000000 ? number_format($chartMax * (3 - $line) / 3 / 1000000, 1, ',', '.') . ' jt' : number_format($chartMax * (3 - $line) / 3 / 1000, 0, ',', '.') . ' rb' }}
                        </text>
                    @endforeach

                    <!-- Area bayangan gradien pengeluaran -->
                    <path d="M 42 180 L {{ implode(' L ', $outPoints) }} L 612 180 Z" fill="url(#cash-out-fill)"/>

                    <!-- Garis pengeluaran (oranye) -->
                    <path d="M {{ implode(' L ', $outPoints) }}" fill="none" stroke="#f67113" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>

                    <!-- Garis pemasukan (biru sapphire) -->
                    <path d="M {{ implode(' L ', $inPoints) }}" fill="none" stroke="#00478a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>

                    <!-- Titik-titik data dan tanggal -->
                    @foreach($plotDays as $day)
                        @php $x = round(42 + $loop->index / $plotDenominator * 570, 2); @endphp
                        <circle cx="{{ $x }}" cy="{{ round(180 - $day['in'] / $chartMax * 145, 2) }}" r="3.5" fill="white" stroke="#00478a" stroke-width="2">
                            <title>{{ $day['label'] }} · Pemasukan Rp {{ number_format($day['in'], 0, ',', '.') }}</title>
                        </circle>
                        <circle cx="{{ $x }}" cy="{{ round(180 - $day['out'] / $chartMax * 145, 2) }}" r="3" fill="white" stroke="#f67113" stroke-width="2">
                            <title>{{ $day['label'] }} · Pengeluaran Rp {{ number_format($day['out'], 0, ',', '.') }}</title>
                        </circle>
                        @if($loop->index % $labelStep === 0 || $loop->last)
                            <text x="{{ $x }}" y="207" text-anchor="middle" fill="#44474f" font-size="10">
                                {{ \Carbon\Carbon::parse($day['date'])->format('d/m') }}
                            </text>
                        @endif
                    @endforeach
                </svg>
            </div>

            <!-- Rincian Tabel Dropdown Sesuai Refrensi -->
            <details class="mt-3 text-xs text-brand-textGray">
                <summary class="cursor-pointer font-medium hover:text-brand-primary">Lihat rincian angka grafik harian</summary>
                <div class="mt-3 max-h-48 overflow-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Pemasukan</th>
                                <th>Pengeluaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plotDays as $day)
                                <tr>
                                    <td>{{ $day['label'] }}</td>
                                    <td class="font-medium text-brand-primary">Rp {{ number_format($day['in'], 0, ',', '.') }}</td>
                                    <td class="text-red-700">Rp {{ number_format($day['out'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </section>

        <!-- Komposisi Kas Donut -->
        <section class="panel flex min-w-0 flex-col p-6 lg:col-span-5">
            <h2 class="text-base font-semibold">Komposisi kas</h2>
            <p class="mt-1 text-xs text-brand-textGray">Perbandingan pemasukan dan pengeluaran</p>
            <div class="flex flex-1 flex-wrap items-center justify-center gap-6 py-6">
                <div class="relative h-40 w-40 shrink-0 rounded-full" style="background: {{ $cashTotal > 0 ? 'conic-gradient(#00478a 0% ' . $inPercent . '%, #f67113 ' . $inPercent . '% 100%)' : '#eaeef2' }};" role="img" aria-label="Pemasukan {{ $inPercent }}% dan pengeluaran {{ $outPercent }}%">
                    <div class="absolute inset-4 flex flex-col items-center justify-center rounded-full bg-white px-2 text-center">
                        <span class="text-[10px] text-brand-textGray">Total pergerakan</span>
                        <span class="mt-1 font-heading text-lg font-bold">
                            {{ $cashTotal >= 1000000 ? 'Rp ' . number_format($cashTotal / 1000000, 1, ',', '.') . ' jt' : 'Rp ' . number_format($cashTotal, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                <dl class="min-w-36 flex-1 space-y-5">
                    <div>
                        <dt class="flex items-center gap-2 text-xs">
                            <span class="chart-legend-dot bg-brand-primary"></span>Pemasukan <span class="ml-auto font-semibold">{{ $inPercent }}%</span>
                        </dt>
                        <dd class="mt-1 pl-4 text-[11px] text-brand-textGray">Rp {{ number_format($cashIn, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="flex items-center gap-2 text-xs">
                            <span class="chart-legend-dot bg-chart-accent"></span>Pengeluaran <span class="ml-auto font-semibold">{{ $outPercent }}%</span>
                        </dt>
                        <dd class="mt-1 pl-4 text-[11px] text-brand-textGray">Rp {{ number_format($cashOut, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>
            <dl class="grid grid-cols-2 gap-4 border-t border-[#eaeef2] pt-4">
                <div class="min-w-0">
                    <dt class="text-[11px] text-brand-textGray">Total piutang</dt>
                    <dd class="mt-1 break-words font-heading text-base font-bold">Rp {{ number_format($summary['total_debt'], 0, ',', '.') }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-[11px] text-brand-textGray">Arus kas bersih</dt>
                    <dd class="mt-1 break-words font-heading text-base font-bold text-brand-primary">Rp {{ number_format($summary['net_cash'], 0, ',', '.') }}</dd>
                </div>
            </dl>
            <a href="{{ route('cash.index') }}" class="mt-4 flex items-center justify-between text-xs font-medium text-brand-primary">
                Buka catatan kas lengkap<x-icon name="arrow"/>
            </a>
        </section>
    </div>

    <!-- Baris 2: Grafik Batang Produk Terlaris & Rincian Omzet Produk Terlaris -->
    <div class="mb-6 grid gap-4 lg:grid-cols-12">
        <!-- Grafik Batang Produk Terlaris -->
        <section class="panel min-w-0 p-6 lg:col-span-8">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
                <div>
                    <h2 class="text-base font-semibold">Produk ayam terlaris</h2>
                    <p class="mt-1 text-xs text-brand-textGray">Volume penjualan produk berdasarkan kuantitas (kg / ekor) terjual</p>
                </div>
                <span class="rounded-lg bg-[#f0f4f8] px-2.5 py-1 text-[11px] text-brand-textGray font-medium">Berdasarkan kuantitas</span>
            </div>

            @if(count($topProducts) > 0)
                <div class="mt-4 overflow-x-auto">
                    <div class="relative flex h-52 min-w-[420px] items-end gap-4 border-b border-[#dfe3e7] px-4 pb-7 pt-4">
                        <!-- Garis skala putus-putus -->
                        <div class="pointer-events-none absolute inset-x-0 top-0 flex h-44 flex-col justify-between">
                            @foreach(range(0, 3) as $line)
                                <div class="border-t border-dashed border-[#dfe3e7]">
                                    <span class="relative -top-2 bg-white pr-2 text-[9px] text-[#747780]">
                                        {{ number_format($maxTopQty * (3 - $line) / 3, 1, ',', '.') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <!-- Batang chart setiap produk terlaris -->
                        @foreach($topProducts as $tp)
                            @php
                                $barH = $maxTopQty > 0 ? round(($tp['total_qty'] / $maxTopQty) * 100) : 0;
                            @endphp
                            <div class="relative z-10 flex h-40 min-w-10 flex-1 flex-col items-center justify-end">
                                <div class="chart-bar w-8 max-w-12 rounded-t-lg bg-brand-primary" style="height: {{ max(6, $barH) }}%;" title="{{ $tp['name'] }} · Terjual: {{ number_format($tp['total_qty'], 2, ',', '.') }} {{ $tp['unit'] }}">
                                </div>
                                <span class="absolute -bottom-6 w-full truncate text-center text-[10px] font-medium text-brand-textDark" title="{{ $tp['name'] }}">
                                    {{ Str::limit($tp['name'], 12) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="empty-state py-8">
                    <p class="text-xs text-brand-textGray">Belum ada item penjualan tercatat.</p>
                </div>
            @endif
        </section>

        <!-- Daftar Rincian Produk Terlaris -->
        <section class="panel flex min-w-0 flex-col p-6 lg:col-span-4">
            <h2 class="text-base font-semibold">Rincian penjualan produk</h2>
            <p class="mt-1 text-xs text-brand-textGray">Total kuantitas & omzet produk</p>

            <div class="mt-4 flex-1 space-y-3">
                @forelse($topProducts as $tp)
                    <div class="flex items-center justify-between gap-2 rounded-xl py-2 transition-colors hover:bg-[#f0f4f8]">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#eef4ff] text-brand-primary">
                            <x-icon name="box"/>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-brand-textDark">{{ $tp['name'] }}</p>
                            <p class="text-[10px] text-brand-textGray">{{ number_format($tp['total_qty'], 1, ',', '.') }} {{ $tp['unit'] }} terjual</p>
                        </div>
                        <span class="text-xs font-bold text-brand-primary whitespace-nowrap">
                            Rp {{ number_format($tp['total_sales'], 0, ',', '.') }}
                        </span>
                    </div>
                @empty
                    <div class="empty-state py-8">
                        <p class="text-xs text-brand-textGray">Belum ada data produk terjual.</p>
                    </div>
                @endforelse
            </div>

            <a href="{{ route('products.index') }}" class="mt-4 flex items-center justify-between border-t border-[#eaeef2] pt-4 text-xs font-medium text-brand-primary">
                Kelola seluruh produk<x-icon name="arrow"/>
            </a>
        </section>
    </div>

    <!-- Baris 3: Operasional Grid: Batch Pemotongan & Peringatan Stok Menipis -->
    <div class="mb-6 grid gap-4 lg:grid-cols-12">
        <!-- Batch Produksi Aktif -->
        <section class="panel min-w-0 p-6 lg:col-span-8">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold">Batch pemotongan ayam aktif</h2>
                    <p class="mt-1 text-xs text-brand-textGray">Ayam hidup yang sedang dalam proses potong & karkas</p>
                </div>
                <a href="{{ route('productions.index') }}" class="text-xs font-medium text-brand-primary">Semua batch →</a>
            </div>

            <div class="mt-4 divide-y divide-[#eaeef2]">
                @forelse($activeProductions as $batch)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('productions.show', $batch->id) }}" class="font-semibold text-brand-primary">{{ $batch->batch_number }}</a>
                            <p class="mt-1 text-xs text-brand-textGray">
                                Berat hidup: <strong class="text-brand-textDark">{{ number_format($batch->live_weight_kg, 2, ',', '.') }} kg</strong> · {{ $batch->live_bird_count ? $batch->live_bird_count . ' ekor' : 'Tanpa hitung ekor' }} · Biaya: Rp {{ number_format($batch->raw_material_cost, 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="badge badge-warning">Dalam proses</span>
                            <a href="{{ route('productions.show', $batch->id) }}" class="btn">Timbang karkas</a>
                        </div>
                    </div>
                @empty
                    <div class="empty-state py-8">
                        <x-icon name="leaf" class="mx-auto mb-2 h-7 w-7 text-brand-textGray"/>
                        <p>Tidak ada batch pemotongan yang sedang aktif.</p>
                        <a href="{{ route('productions.form') }}" class="mt-2 inline-block font-semibold text-brand-primary">Mulai batch pemotongan baru →</a>
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Peringatan Stok Menipis -->
        <section class="panel flex min-w-0 flex-col p-6 lg:col-span-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold">Stok menipis</h2>
                    <p class="mt-1 text-xs text-brand-textGray">Produk pada batas minimum</p>
                </div>
                <span class="badge badge-danger">{{ $lowStockProducts->count() }} produk</span>
            </div>

            <div class="mt-4 flex-1 space-y-3">
                @forelse($lowStockProducts as $prod)
                    <div class="flex items-center justify-between gap-2 rounded-xl py-2 transition-colors hover:bg-[#f0f4f8]">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#eef4ff] text-brand-primary"><x-icon name="box"/></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold">{{ $prod->name }}</p>
                            <p class="text-[10px] text-brand-textGray">Min: {{ number_format($prod->min_stock, 1, ',', '.') }} {{ $prod->unit }}</p>
                        </div>
                        <span class="badge badge-danger font-semibold">
                            {{ number_format($prod->stock, 2, ',', '.') }} {{ $prod->unit }}
                        </span>
                    </div>
                @empty
                    <div class="empty-state px-0 py-8">
                        <x-icon name="check" class="mx-auto mb-2 h-6 w-6 text-emerald-600"/>
                        <p class="text-xs text-brand-textGray">Seluruh stok produk berada di atas batas minimum.</p>
                    </div>
                @endforelse
            </div>
            <a href="{{ route('products.index') }}" class="mt-4 flex items-center justify-between border-t border-[#eaeef2] pt-4 text-xs font-medium text-brand-primary">
                Kelola persediaan produk<x-icon name="arrow"/>
            </a>
        </section>
    </div>

    <!-- Baris 4: Piutang Jatuh Tempo & Transaksi Terakhir -->
    <div class="grid gap-4 lg:grid-cols-12">
        <section class="panel min-w-0 p-6 lg:col-span-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold">Tagihan piutang jatuh tempo</h2>
                    <p class="mt-1 text-xs text-brand-textGray">Mendekati H-3 atau sudah lewat batas tempo</p>
                </div>
                <a href="{{ route('sales.index') }}" class="text-xs font-medium text-brand-primary">Kelola piutang →</a>
            </div>

            <div class="mt-4 divide-y divide-[#eaeef2]">
                @forelse($maturingDebts as $sale)
                    <div class="flex items-center justify-between py-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-brand-textDark">{{ $sale->customer?->name ?? 'Pelanggan Umum' }}</p>
                            <p class="text-[11px] text-brand-textGray">{{ $sale->invoice_number }} · Jatuh tempo: {{ $sale->due_date ? \Carbon\Carbon::parse($sale->due_date)->format('d/m/Y') : '-' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-heading text-xs font-bold text-red-700">Rp {{ number_format($sale->remaining_debt, 0, ',', '.') }}</p>
                            <a href="{{ route('sales.index', ['search' => $sale->invoice_number]) }}" class="text-[11px] font-semibold text-brand-primary hover:underline">Pelunasan</a>
                        </div>
                    </div>
                @empty
                    <div class="empty-state py-6">
                        <p class="text-xs text-brand-textGray">Tidak ada piutang jatuh tempo dalam waktu dekat.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="panel min-w-0 p-6 lg:col-span-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold">Transaksi kasir terbaru</h2>
                    <p class="mt-1 text-xs text-brand-textGray">Penjualan dan pembayaran terakhir</p>
                </div>
                <a href="{{ route('sales.index') }}" class="text-xs font-medium text-brand-primary">Lihat semua →</a>
            </div>

            <div class="mt-4 divide-y divide-[#eaeef2]">
                @forelse($recentSales as $sale)
                    <div class="flex items-center justify-between py-3">
                        <div class="min-w-0">
                            <a href="{{ route('sales.index', ['search' => $sale->invoice_number]) }}" class="font-semibold text-brand-primary text-xs">{{ $sale->invoice_number }}</a>
                            <p class="text-[11px] text-brand-textGray">{{ $sale->customer?->name ?? 'Umum' }} · {{ \Carbon\Carbon::parse($sale->transaction_date)->format('d/m/Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-heading text-xs font-bold">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</p>
                            <span @class(['badge', 'badge-warning' => $sale->payment_status !== 'paid'])>{{ $sale->payment_status === 'paid' ? 'Lunas' : 'Tempo' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="empty-state py-6">
                        <p class="text-xs text-brand-textGray">Belum ada transaksi penjualan.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
