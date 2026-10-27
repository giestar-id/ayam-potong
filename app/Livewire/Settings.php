<?php

namespace App\Livewire;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Settings extends Component
{
    // Business Settings
    public string $business_name = '';
    public string $address = '';
    public string $phone = '';
    public string $receipt_footer = '';

    // Profile Settings
    public string $name = '';
    public string $email = '';

    // Password Settings
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $biz = BusinessSetting::current();
        $this->business_name = $biz->business_name;
        $this->address = (string) ($biz->address ?? '');
        $this->phone = (string) ($biz->phone ?? '');
        $this->receipt_footer = (string) ($biz->receipt_footer ?? '');

        $user = Auth::user();
        if ($user) {
            $this->name = $user->name;
            $this->email = $user->email;
        }
    }

    public function updateBusiness(): void
    {
        $this->validate([
            'business_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'receipt_footer' => 'nullable|string|max:500',
        ]);

        $biz = BusinessSetting::firstOrCreate([]);
        $biz->update([
            'business_name' => $this->business_name,
            'address' => $this->address ?: null,
            'phone' => $this->phone ?: null,
            'receipt_footer' => $this->receipt_footer ?: null,
        ]);

        BusinessSetting::forgetCache();
        session()->flash('success_biz', 'Informasi usaha dan format struk berhasil diperbarui.');
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
        ]);

        Auth::user()->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        session()->flash('success_profile', 'Profil pemilik berhasil diperbarui.');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        Auth::user()->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('success_password', 'Kata sandi akun berhasil diubah.');
    }

    public function render(): View
    {
        return view('livewire.settings');
    }
}
