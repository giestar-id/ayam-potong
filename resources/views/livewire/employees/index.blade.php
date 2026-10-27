<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Manajemen tim</p>
            <h1 class="page-title">Karyawan & pekerja</h1>
            <p class="page-description">Kelola tim jagal potong, penimbangan, dan tarif gaji harian untuk perhitungan gaji.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="openCreateModal" class="btn btn-primary">
                <x-icon name="plus"/>Tambah pekerja
            </button>
            <a href="{{ route('payrolls.index') }}" class="btn">
                <x-icon name="wallet"/>Pembayaran gaji
            </a>
        </div>
    </div>
    <x-feedback/>

    <!-- 4 Stat Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Total pekerja" :value="number_format($employees->total()) . ' orang'" note="Seluruh tim terdaftar" icon="users"/>
        <x-stat label="Pekerja aktif" :value="number_format(\App\Models\Employee::where('is_active', true)->count()) . ' orang'" note="Aktif bertugas" icon="users"/>
        <x-stat label="Rata-rata tarif harian" :value="'Rp ' . number_format((float)\App\Models\Employee::where('is_active', true)->avg('daily_rate'), 0, ',', '.')" note="Dasar upah per hari kerja" icon="wallet"/>
        <x-stat label="Riwayat penggajian" :value="number_format(\App\Models\Payroll::count()) . ' slip'" note="Total pembayaran gaji tercatat" icon="clock"/>
    </div>

    <!-- Tabel Daftar Karyawan (Full Width) -->
    <section class="panel min-w-0 overflow-hidden w-full mb-6">
        <div class="filters">
            <label class="field flex-1 min-w-44">
                Cari pekerja
                <input wire:model.live.debounce.300ms="search" class="input" placeholder="Cari nama atau posisi...">
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama pekerja</th>
                        <th>Posisi</th>
                        <th>Telepon</th>
                        <th class="text-right">Tarif harian</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        <tr wire:key="emp-{{ $emp->id }}">
                            <td class="font-semibold text-brand-textDark">{{ $emp->name }}</td>
                            <td class="whitespace-nowrap">{{ $emp->position ?? '-' }}</td>
                            <td class="whitespace-nowrap text-brand-textGray">{{ $emp->phone ?? '-' }}</td>
                            <td class="whitespace-nowrap text-right font-medium">Rp {{ number_format($emp->daily_rate, 0, ',', '.') }} / hari</td>
                            <td>
                                <span class="badge {{ $emp->is_active ? '' : 'badge-danger' }}">
                                    {{ $emp->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" wire:click="edit({{ $emp->id }})" class="btn text-xs py-1 px-2.5">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="delete({{ $emp->id }})" wire:confirm="Hapus data pekerja ini?" class="btn btn-danger text-xs py-1 px-2.5">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">Belum ada data pekerja yang tercatat.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-[#eaeef2] p-5">
            {{ $employees->links() }}
        </div>
    </section>

    <!-- Modal Form Tambah/Ubah Karyawan (Muncul di Tengah dengan Background Blur) -->
    @if($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-sm sm:backdrop-blur-md"
             x-data
             @keydown.escape.window="$wire.closeFormModal()">
            <section role="dialog" aria-modal="true" class="panel relative max-h-[92dvh] overflow-y-auto p-4 shadow-2xl" style="max-width: 380px; width: 100%;">
                <div class="mb-3 flex items-start justify-between gap-2 border-b border-[#eaeef2] pb-2.5">
                    <div>
                        <p class="eyebrow text-[10px]">Data Tim</p>
                        <h2 class="text-sm font-bold text-brand-textDark">{{ $employeeId ? 'Ubah data pekerja' : 'Tambah pekerja baru' }}</h2>
                        <p class="mt-0.5 text-[11px] text-brand-textGray">Atur nama, posisi, dan tarif upah harian.</p>
                    </div>
                    <button type="button" class="btn text-xs py-1 px-2" wire:click="closeFormModal" aria-label="Tutup"><x-icon name="close"/></button>
                </div>

                <form wire:submit="save" class="space-y-2.5 text-xs">
                    <label class="field">
                        Nama karyawan / pekerja *
                        <input wire:model="name" class="input text-xs" placeholder="Nama lengkap pekerja" required autofocus>
                    </label>
                    @error('name') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                    <label class="field">
                        Posisi / tugas
                        <input wire:model="position" class="input text-xs" placeholder="Misal: Juru Potong, Supir">
                    </label>

                    <label class="field">
                        Nomor WhatsApp / telepon
                        <input wire:model="phone" class="input text-xs" placeholder="08xxxxxxxxxx">
                    </label>

                    <label class="field">
                        Tarif upah harian (Rp / hari) *
                        <input wire:model="daily_rate" type="number" step="1000" class="input font-semibold text-xs" placeholder="Contoh: 125000" required>
                    </label>
                    @error('daily_rate') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                    <label class="flex items-center gap-2 text-xs font-medium text-brand-textDark cursor-pointer pt-1">
                        <input wire:model="is_active" type="checkbox">
                        Karyawan aktif (dihitung pada gaji)
                    </label>

                    <div class="flex items-center justify-end gap-2 pt-2.5 border-t border-[#eaeef2] mt-3">
                        <button type="button" wire:click="closeFormModal" class="btn text-xs py-1.5 px-3">Batal</button>
                        <button type="submit" class="btn btn-primary text-xs py-1.5 px-3" wire:loading.attr="disabled">
                            <x-icon name="check"/>{{ $employeeId ? 'Simpan' : 'Tambah' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>
