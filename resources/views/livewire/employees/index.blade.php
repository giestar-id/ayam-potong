<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Manajemen tim</p>
            <h1 class="page-title">Pegawai gerai</h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" wire:click="openCreateModal" class="btn btn-primary">
                <x-icon name="plus"/>Tambah pegawai
            </button>
            <a href="{{ route('payrolls.index') }}" class="btn">
                <x-icon name="wallet"/>Pembayaran gaji
            </a>
        </div>
    </div>
    <x-feedback/>

    <!-- Stat Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-stat label="Total pegawai" :value="number_format($employees->total()) . ' orang'" note="Seluruh pegawai terdaftar" icon="users"/>
        <x-stat label="Pegawai aktif" :value="number_format(\App\Models\Employee::where('is_active', true)->count()) . ' orang'" note="Dapat login & bertugas" icon="users"/>
        <x-stat label="Hak akses dashboard" value="Pegawai" note="Beranda, Produksi, Kasir, Penjualan, Produk" icon="box"/>
    </div>

    <!-- Tabel Daftar Pegawai (Full Width) -->
    <section class="panel min-w-0 overflow-hidden w-full mb-6">
        <div class="filters">
            <label class="field flex-1 min-w-44">
                Cari pegawai
                <input wire:model.live.debounce.300ms="search" class="input" placeholder="Cari nama atau nomor HP...">
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama pegawai</th>
                        <th>Nomor HP (Akun Login)</th>
                        <th>Posisi</th>
                        <th>Gaji Harian</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        <tr wire:key="emp-{{ $emp->id }}">
                            <td class="font-semibold text-brand-textDark">{{ $emp->name }}</td>
                            <td class="whitespace-nowrap font-mono text-xs text-brand-primary font-medium">{{ $emp->phone ?? '-' }}</td>
                            <td class="whitespace-nowrap text-xs text-brand-textDark font-medium">{{ $emp->position ?: 'Pegawai' }}</td>
                            <td class="whitespace-nowrap font-semibold text-emerald-600 text-xs">
                                Rp {{ number_format($emp->daily_rate ?: 0, 0, ',', '.') }} <span class="text-[10px] text-brand-textGray font-normal">/ hari</span>
                            </td>
                            <td>
                                <span class="badge {{ $emp->is_active ? '' : 'badge-danger' }}">
                                    {{ $emp->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" wire:click="edit({{ $emp->id }})" class="btn text-xs py-1 px-2.5">
                                        Edit / Password
                                    </button>
                                    <button type="button" wire:click="delete({{ $emp->id }})" wire:confirm="Hapus data dan akun pegawai ini?" class="btn btn-danger text-xs py-1 px-2.5">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">Belum ada data pegawai yang tercatat.</div>
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

    <!-- Modal Form Tambah/Ubah Pegawai -->
    @if($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-sm sm:backdrop-blur-md"
             x-data
             @keydown.escape.window="$wire.closeFormModal()">
            <section role="dialog" aria-modal="true" class="panel relative max-h-[92dvh] overflow-y-auto p-5 shadow-2xl" style="max-width: 400px; width: 100%;">
                <div class="mb-3.5 flex items-start justify-between gap-2 border-b border-[#eaeef2] pb-3">
                    <div>
                        <p class="eyebrow text-[10px]">Data Akun</p>
                        <h2 class="text-base font-bold text-brand-textDark">{{ $employeeId ? 'Ubah data & password pegawai' : 'Tambah pegawai baru' }}</h2>
                    </div>
                    <button type="button" class="btn text-xs py-1 px-2" wire:click="closeFormModal" aria-label="Tutup"><x-icon name="close"/></button>
                </div>

                <form wire:submit="save" class="space-y-3.5 text-xs">
                    <label class="field">
                        Nama pegawai *
                        <input wire:model="name" class="input text-xs" placeholder="Nama lengkap pegawai" required autofocus>
                    </label>
                    @error('name') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                    <label class="field">
                        Nomor HP / WhatsApp (Digunakan untuk login) *
                        <input wire:model="phone" class="input text-xs" placeholder="Contoh: 081234567890" required>
                    </label>
                    @error('phone') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                    <label class="field">
                        Posisi / Bagian Pekerjaan
                        <input wire:model="position" class="input text-xs" placeholder="Contoh: Pemotong, Parting, Kasir">
                    </label>
                    @error('position') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                    <label class="field">
                        Tarif Gaji Harian (Rp / hari) *
                        <input wire:model="daily_rate" type="number" step="1000" min="0" class="input text-xs" placeholder="Contoh: 100000" required>
                    </label>
                    @error('daily_rate') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                    <label class="field">
                        {{ $employeeId ? 'Ganti kata sandi / password baru' : 'Kata sandi / password login *' }}
                        <input wire:model="password" type="text" class="input text-xs" placeholder="{{ $employeeId ? 'Kosongkan jika tidak ingin mengubah password' : 'Minimal 4 karakter' }}" {{ $employeeId ? '' : 'required' }}>
                        @if($employeeId)
                            <span class="text-[10px] text-brand-textGray block mt-0.5">Biarkan kosong jika tetap menggunakan password lama.</span>
                        @endif
                    </label>
                    @error('password') <span class="text-[11px] text-red-600">{{ $message }}</span> @enderror

                    <label class="flex items-center gap-2 text-xs font-medium text-brand-textDark cursor-pointer pt-1">
                        <input wire:model="is_active" type="checkbox" class="rounded border-gray-300">
                        Akun aktif (bisa login ke sistem)
                    </label>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#eaeef2] mt-4">
                        <button type="button" wire:click="closeFormModal" class="btn text-xs py-1.5 px-3">Batal</button>
                        <button type="submit" class="btn btn-primary text-xs py-1.5 px-3" wire:loading.attr="disabled">
                            <x-icon name="check"/>{{ $employeeId ? 'Simpan Perubahan' : 'Tambah Pegawai' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>
