<?php

namespace App\Livewire\Auth;

use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Masuk · Sistem POS Ayam Potong')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function mount(): void
    {
        // Pre-fill default owner email for convenience in demo
        if ($this->email === '') {
            $this->email = 'admin@ayampotong.com';
        }
    }

    public function login(): mixed
    {
        $credentials = $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean',
        ], [
            'email.required' => 'Email wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $key = 'login:' . Str::lower($this->email) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Terlalu banyak percobaan login. Silakan tunggu ' . RateLimiter::availableIn($key) . ' detik.');
            return null;
        }

        unset($credentials['remember']);

        if (Auth::attempt($credentials, (bool) $this->remember)) {
            RateLimiter::clear($key);
            session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($key, 60);
        $this->addError('email', 'Email atau kata sandi tidak sesuai.');

        return null;
    }

    public function render(): View
    {
        return view('livewire.auth.login', [
            'businessName' => BusinessSetting::current()->business_name,
        ]);
    }
}
