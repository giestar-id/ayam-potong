<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Workspace</p>
            <h1 class="page-title">Pengaturan</h1>
            <p class="page-description">Identitas usaha ayam potong, rincian struk, dan keamanan akses pemilik.</p>
        </div>
    </div>
    <x-feedback/>

    @if(session()->has('success_biz') || session()->has('success_profile') || session()->has('success_password'))
        <div role="status" class="mb-5 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <p class="font-semibold">{{ session('success_biz') ?? session('success_profile') ?? session('success_password') }}</p>
        </div>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-2">
        <!-- Pengaturan Identitas Usaha & Struk -->
        <section class="panel p-6">
            <span class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-[#eef4ff] text-brand-primary">
                <x-icon name="box"/>
            </span>
            <h2 class="text-base font-semibold">Identitas usaha & struk</h2>
            <p class="mb-6 mt-2 text-xs leading-6 text-brand-textGray">Nama dan kontak ini ditampilkan di navigasi dan struk kasir yang dicetak.</p>

            <form wire:submit="updateBusiness" class="space-y-4">
                <label class="field">
                    Nama usaha / toko *
                    <input wire:model="business_name" class="input font-semibold" required>
                </label>
                @error('business_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror

                <label class="field">
                    Nomor WhatsApp / telepon
                    <input wire:model="phone" class="input" placeholder="08xxxxxxxxxx">
                </label>

                <label class="field">
                    Alamat usaha
                    <textarea wire:model="address" rows="2" class="input" placeholder="Alamat outlet / tempat pemotongan"></textarea>
                </label>

                <label class="field">
                    Catatan footer struk kasir
                    <textarea wire:model="receipt_footer" rows="2" class="input" placeholder="Pesan terima kasih / info halal dll."></textarea>
                </label>

                <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                    <x-icon name="check"/>Simpan informasi usaha
                </button>
            </form>
        </section>

        <!-- Profil & Keamanan Akun -->
        <div class="space-y-6">
            <section class="panel p-6">
                <span class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-[#eef4ff] text-brand-primary">
                    <x-icon name="users"/>
                </span>
                <h2 class="text-base font-semibold">Profil pemilik</h2>
                <p class="mb-6 mt-2 text-xs leading-6 text-brand-textGray">Nama dan alamat email yang digunakan untuk masuk ke ruang kerja.</p>

                <form wire:submit="updateProfile" class="space-y-4">
                    <label class="field">
                        Nama lengkap *
                        <input wire:model="name" class="input" required>
                    </label>
                    @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror

                    <label class="field">
                        Alamat email *
                        <input wire:model="email" type="email" class="input" required>
                    </label>
                    @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror

                    <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                        <x-icon name="check"/>Simpan profil
                    </button>
                </form>
            </section>

            <section class="panel p-6">
                <span class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-[#eef4ff] text-brand-primary">
                    <x-icon name="settings"/>
                </span>
                <h2 class="text-base font-semibold">Ubah password</h2>
                <p class="mb-6 mt-2 text-xs leading-6 text-brand-textGray">Gunakan minimal 8 karakter untuk menjaga keamanan akses akun.</p>

                <form wire:submit="updatePassword" class="space-y-4">
                    <label class="field">
                        Password saat ini
                        <input wire:model="current_password" type="password" class="input" required>
                    </label>
                    @error('current_password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror

                    <label class="field">
                        Password baru
                        <input wire:model="new_password" type="password" class="input" minlength="8" required>
                    </label>
                    @error('new_password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror

                    <label class="field">
                        Konfirmasi password baru
                        <input wire:model="new_password_confirmation" type="password" class="input" minlength="8" required>
                    </label>

                    <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                        <x-icon name="check"/>Perbarui password
                    </button>
                </form>
            </section>
        </div>
    </div>
</div>
