<div x-data="{ cartOpen: false }" @keydown.escape.window="cartOpen = false" class="pb-24 xl:pb-6">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Keuangan & tim</p>
            <h1 class="page-title">Pembayaran gaji</h1>
            <p class="page-description">Pilih pekerja, atur hari kerja mingguan, lalu bayar sekaligus ke kas pengeluaran.</p>
        </div>
        <a href="{{ route('employees.index') }}" class="btn">
            <x-icon name="users"/>Kelola data pekerja
        </a>
    </div>
    <x-feedback/>

    <!-- 4 Stat Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Total pengeluaran gaji" :value="'Rp ' . number_format($totalPayrollPaid, 0, ',', '.')" note="Seluruh pembayaran tercatat" icon="wallet"/>
        <x-stat label="Gaji bulan ini" :value="'Rp ' . number_format($thisMonthPayroll, 0, ',', '.')" note="Pengeluaran bulan berjalan" icon="up"/>
        <x-stat label="Pekerja aktif" :value="number_format($activeEmployeesCount) . ' orang'" note="Siap menerima upah" icon="users"/>
        <x-stat label="Slip gaji terbit" :value="number_format($payrolls->total()) . ' slip'" note="Riwayat transaksi gaji" icon="clock"/>
    </div>

    <!-- Mobile Sticky Cart Bar -->
    <div x-show="cartOpen" x-cloak @click="cartOpen = false" class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-xs xl:hidden"></div>
    <button type="button"
            @click="cartOpen = !cartOpen"
            :aria-expanded="cartOpen"
            class="fixed inset-x-4 bottom-4 z-40 flex items-center justify-between rounded-2xl bg-brand-primary px-5 py-3.5 text-sm font-semibold text-white shadow-xl transition-transform active:scale-[0.98] xl:hidden">
        <div class="flex items-center gap-2.5">
            <x-icon name="wallet" class="h-5 w-5"/>
            <span>Keranjang Gaji ({{ count($cart) }} pekerja)</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="font-bold">Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>
            <svg class="h-5 w-5 transition-transform" :class="cartOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m18 15-6-6-6 6"/>
            </svg>
        </div>
    </button>

    <!-- POS Payroll Grid (Cards + Cart Drawer) -->
    <div class="mb-8 grid items-start gap-6 xl:grid-cols-[1fr_390px]">
        <!-- Kolom Kiri: Kartu Karyawan Siap Bayar -->
        <section class="panel min-w-0 overflow-hidden">
            <div class="filters flex-wrap gap-3">
                <label class="field flex-1 min-w-44">
                    Cari pekerja
                    <input wire:model.live.debounce.300ms="employeeSearch" class="input" placeholder="Nama atau posisi pekerja...">
                </label>
                <div class="flex items-center gap-2 pt-2 sm:pt-5">
                    <button type="button" wire:click="selectAllEmployees" class="btn text-xs py-1.5 px-3" title="Masukkan semua pekerja aktif ke keranjang">
                        <x-icon name="check"/>Pilih semua
                    </button>
                    @if(count($cart) > 0)
                        <button type="button" wire:click="clearCart" class="btn text-xs py-1.5 px-3 text-red-600 hover:text-red-700">
                            Kosongkan
                        </button>
                    @endif
                </div>
            </div>

            <div class="p-4 sm:p-5">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 2xl:grid-cols-3">
                    @forelse($availableEmployees as $emp)
                        @php
                            $isSelected = isset($cart[$emp->id]);
                        @endphp
                        <div wire:key="emp-card-{{ $emp->id }}"
                             wire:click="toggleCart({{ $emp->id }})"
                             class="group relative flex cursor-pointer flex-col justify-between rounded-xl border p-4 transition-all duration-150 {{ $isSelected ? 'border-brand-primary bg-[#f0f6ff] shadow-sm ring-2 ring-brand-primary/20' : 'border-[#eaeef2] bg-white hover:border-brand-primary/40 hover:bg-[#fafcfe]' }}">
                            <div>
                                <div class="mb-2 flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg font-bold text-xs {{ $isSelected ? 'bg-brand-primary text-white' : 'bg-[#eef4ff] text-brand-primary' }}">
                                            {{ mb_strtoupper(mb_substr($emp->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-sm leading-tight text-brand-textDark">{{ $emp->name }}</h3>
                                            <p class="mt-0.5 text-[11px] text-brand-textGray">{{ $emp->position ?? 'Pekerja' }}</p>
                                        </div>
                                    </div>
                                    @if($isSelected)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-primary/10 px-2 py-0.5 text-[10px] font-semibold text-brand-primary">
                                            <x-icon name="check" class="h-3 w-3"/>Terpilih
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between border-t border-[#eaeef2] pt-2.5">
                                <div>
                                    <p class="text-[10px] text-brand-textGray">Tarif upah harian</p>
                                    <strong class="text-sm font-bold text-brand-primary">Rp {{ number_format($emp->daily_rate, 0, ',', '.') }}</strong>
                                </div>
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg border text-xs transition-colors {{ $isSelected ? 'border-brand-primary bg-brand-primary text-white' : 'border-[#d0d7de] bg-white text-brand-textGray group-hover:border-brand-primary group-hover:text-brand-primary' }}">
                                    @if($isSelected)
                                        <x-icon name="check" class="h-3.5 w-3.5"/>
                                    @else
                                        <x-icon name="plus" class="h-3.5 w-3.5"/>
                                    @endif
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-8 text-center text-xs text-brand-textGray">
                            Belum ada data pekerja aktif. Silakan tambah pekerja di menu data pekerja.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <!-- Kolom Kanan: Keranjang Gaji (Cart Panel) -->
        <section id="payroll-cart"
                 class="panel overflow-hidden transition-all duration-200 xl:sticky xl:top-6"
                 :class="cartOpen ? 'fixed inset-x-4 bottom-20 z-50 max-h-[80vh] overflow-y-auto rounded-2xl shadow-2xl xl:static xl:max-h-none xl:shadow-soft' : 'hidden xl:block'">
            <div class="flex items-center justify-between border-b border-[#eaeef2] bg-white p-4 sm:p-5">
                <div>
                    <h2 class="text-base font-bold text-brand-textDark">Keranjang gaji</h2>
                    <p class="mt-0.5 text-xs text-brand-textGray">{{ count($cart) }} pekerja dipilih</p>
                </div>
                @if(count($cart) > 0)
                    <button type="button" wire:click="clearCart" class="btn text-xs py-1 px-2.5 text-red-600 hover:text-red-700">
                        Kosongkan
                    </button>
                @endif
            </div>

            <!-- List Karyawan Terpilih di Keranjang -->
            <div class="max-h-72 overflow-y-auto divide-y divide-[#eaeef2] p-4 sm:p-5">
                @forelse($cartLines as $empId => $item)
                    <div wire:key="cart-item-{{ $empId }}" class="py-3 first:pt-0 last:pb-0">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h4 class="font-semibold text-xs text-brand-textDark">{{ $item['name'] }}</h4>
                                <p class="text-[11px] text-brand-textGray">Rp {{ number_format($item['rate'], 0, ',', '.') }} / hari</p>
                            </div>
                            <button type="button" wire:click="removeItem({{ $empId }})" class="text-brand-textGray hover:text-red-600 p-1" title="Hapus dari keranjang">
                                <x-icon name="close" class="h-3.5 w-3.5"/>
                            </button>
                        </div>

                        <div class="mt-2.5 flex items-center justify-between gap-2">
                            <!-- Stepper Hari Kerja -->
                            <div class="flex items-center rounded-lg border border-brand-border bg-white shadow-xs">
                                <button type="button"
                                        wire:click="decrementDays({{ $empId }}, 1)"
                                        class="flex h-7 w-7 items-center justify-center text-xs font-bold text-brand-textGray hover:bg-slate-100 active:scale-95">
                                    -
                                </button>
                                <input type="number"
                                       step="0.5"
                                       min="0.5"
                                       max="31"
                                       wire:model.live.debounce.300ms="cart.{{ $empId }}.days"
                                       class="h-7 w-12 border-0 bg-transparent text-center text-xs font-bold text-brand-textDark focus:ring-0">
                                <button type="button"
                                        wire:click="incrementDays({{ $empId }}, 1)"
                                        class="flex h-7 w-7 items-center justify-center text-xs font-bold text-brand-textGray hover:bg-slate-100 active:scale-95">
                                    +
                                </button>
                                <span class="pr-2 text-[10px] text-brand-textGray">hari</span>
                            </div>

                            <!-- Subtotal Gaji Karyawan -->
                            <div class="text-right">
                                <span class="text-xs font-bold text-brand-primary">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-brand-textGray">
                        <x-icon name="wallet" class="mx-auto mb-2 h-7 w-7 text-slate-300"/>
                        <p class="font-medium text-slate-600">Keranjang masih kosong</p>
                        <p class="mt-0.5 text-[11px]">Klik kartu pekerja di sebelah kiri untuk menambahkan ke daftar gaji.</p>
                    </div>
                @endforelse
            </div>

            <!-- Form Parameter & Checkout -->
            <form wire:submit="checkout" class="border-t border-[#eaeef2] bg-[#f8fbff] p-4 sm:p-5 space-y-3">
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <label class="field">
                        Periode dari *
                        <input wire:model="period_start" type="date" class="input text-xs" required>
                    </label>
                    <label class="field">
                        Sampai *
                        <input wire:model="period_end" type="date" class="input text-xs" required>
                    </label>
                </div>
                @error('period_end') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <label class="field col-span-2">
                        Tanggal pembayaran kas *
                        <input wire:model="payment_date" type="date" class="input text-xs" required>
                    </label>
                </div>

                <label class="field text-xs">
                    Catatan slip gaji (opsional)
                    <input wire:model="notes" class="input text-xs" placeholder="Contoh: Gaji minggu ke-3">
                </label>

                <!-- Total Pembayaran -->
                <div class="rounded-xl border border-blue-100 bg-white p-3 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-brand-textGray">Total upah seluruh pekerja:</span>
                        <span class="text-base font-bold text-brand-primary">Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>
                    </div>
                    <p class="mt-1 text-[10px] text-brand-textGray leading-tight">
                        Otomatis mencatat slip gaji tiap pekerja dan memotong saldo di Buku Kas.
                    </p>
                </div>

                <button type="submit"
                        class="btn btn-primary w-full py-2.5 text-xs font-bold"
                        wire:loading.attr="disabled"
                        wire:confirm="Bayar seluruh gaji pekerja di keranjang sekarang dan catat ke Pengeluaran kas?"
                        @disabled(count($cart) === 0)>
                    <x-icon name="check"/>
                    <span wire:loading.remove wire:target="checkout">
                        Bayar gaji ({{ count($cart) }} pekerja)
                    </span>
                    <span wire:loading wire:target="checkout">
                        Memproses pembayaran...
                    </span>
                </button>
            </form>
        </section>
    </div>

    <!-- Riwayat Slip Gaji (Full Width Table) -->
    <section class="panel min-w-0 overflow-hidden w-full">
        <div class="filters flex-wrap gap-3">
            <div class="flex-1 min-w-44">
                <h2 class="text-sm font-semibold text-brand-textDark">Riwayat slip gaji terbayar</h2>
                <p class="text-xs text-brand-textGray">Setiap penggajian otomatis tersinkron ke Buku Kas Pengeluaran.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <label class="field">
                    Filter pekerja
                    <select wire:model.live="historyEmployeeFilter" class="input text-xs">
                        <option value="">Semua pekerja</option>
                        @foreach($allEmployees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    Tanggal bayar dari
                    <input wire:model.live="historyDateFrom" type="date" class="input text-xs">
                </label>
                <label class="field">
                    Sampai
                    <input wire:model.live="historyDateTo" type="date" class="input text-xs">
                </label>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pekerja</th>
                        <th>Tanggal bayar</th>
                        <th>Periode kerja</th>
                        <th class="text-center">Hari kerja</th>
                        <th class="text-right">Tarif harian</th>
                        <th class="text-right">Total gaji</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrolls as $p)
                        <tr wire:key="pay-hist-{{ $p->id }}">
                            <td class="font-semibold text-brand-textDark">
                                {{ $p->employee->name ?? 'Pekerja' }}
                                <span class="block text-[10px] text-brand-textGray font-normal">{{ $p->employee->position ?? 'Pekerja' }}</span>
                            </td>
                            <td class="whitespace-nowrap text-brand-textGray">
                                {{ $p->payment_date ? $p->payment_date->translatedFormat('d/m/Y') : '-' }}
                            </td>
                            <td class="whitespace-nowrap text-xs text-brand-textGray">
                                {{ $p->period_start ? $p->period_start->translatedFormat('d/m') : '-' }} s/d {{ $p->period_end ? $p->period_end->translatedFormat('d/m/Y') : '-' }}
                            </td>
                            <td class="whitespace-nowrap text-center font-medium">
                                {{ (float)$p->days_worked }} hari
                            </td>
                            <td class="whitespace-nowrap text-right text-brand-textGray">
                                Rp {{ number_format($p->daily_rate_snapshot, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap text-right font-bold text-brand-primary">
                                Rp {{ number_format($p->total_salary, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <button type="button"
                                        wire:click="deletePayroll({{ $p->id }})"
                                        wire:confirm="Hapus slip gaji ini? Pengeluaran kas terkait juga akan dihapus."
                                        class="btn btn-danger text-xs py-1 px-2.5">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">Belum ada riwayat slip gaji yang sesuai dengan filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-[#eaeef2] p-5">
            {{ $payrolls->links() }}
        </div>
    </section>
</div>
