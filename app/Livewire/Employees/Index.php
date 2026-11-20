<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $name = '';

    public string $phone = '';

    public string $position = 'Pegawai';

    public string|int|float $daily_rate = 100000;

    public string $password = '';

    public bool $is_active = true;

    public bool $showFormModal = false;

    #[Locked]
    public ?int $employeeId = null;

    public function mount(): void
    {
        $this->authorize('owner');
    }

    public function openCreateModal(): void
    {
        $this->authorize('owner');
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $this->authorize('owner');
        $emp = Employee::findOrFail($id);
        $this->employeeId = $emp->id;
        $this->name = $emp->name;
        $this->phone = (string) ($emp->phone ?? '');
        $this->position = $emp->position ?? 'Pegawai';
        $this->daily_rate = $emp->daily_rate > 0 ? (int) $emp->daily_rate : '';
        $this->password = '';
        $this->is_active = (bool) $emp->is_active;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->authorize('owner');

        $emp = $this->employeeId ? Employee::find($this->employeeId) : null;
        $userId = $emp?->user_id;

        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)],
            'position' => 'nullable|string|max:255',
            'daily_rate' => 'nullable|numeric|min:0',
            'password' => $this->employeeId ? 'nullable|string|min:4' : 'required|string|min:4',
        ], [
            'name.required' => 'Nama pegawai wajib diisi.',
            'phone.required' => 'Nomor HP pegawai wajib diisi.',
            'phone.unique' => 'Nomor HP sudah terdaftar untuk pengguna lain.',
            'daily_rate.numeric' => 'Tarif gaji harian harus berupa angka.',
            'daily_rate.min' => 'Tarif gaji harian tidak boleh bernilai negatif.',
            'password.required' => 'Kata sandi wajib diisi untuk akun pegawai.',
            'password.min' => 'Kata sandi minimal 4 karakter.',
        ]);

        $phone = trim($this->phone);
        $name = trim($this->name);
        $pos = trim($this->position ?: 'Pegawai');
        $rate = (float) ($this->daily_rate ?: 0);

        DB::transaction(function () use ($emp, $phone, $name, $pos, $rate) {
            if ($this->employeeId && $emp) {
                $emp->update([
                    'name' => $name,
                    'phone' => $phone,
                    'position' => $pos,
                    'daily_rate' => $rate,
                    'is_active' => $this->is_active,
                ]);

                if ($emp->user_id && $user = User::find($emp->user_id)) {
                    $user->name = $name;
                    $user->phone = $phone;
                    $user->email = $phone.'@ayampotong.com';
                    if (! empty($this->password)) {
                        $user->password = Hash::make($this->password);
                        $user->tokens()->delete();
                    }
                    $user->save();

                    if (! $this->is_active) {
                        $user->tokens()->delete();
                    }
                } elseif (! empty($this->password)) {
                    $user = User::create([
                        'name' => $name,
                        'phone' => $phone,
                        'email' => $phone.'@ayampotong.com',
                        'password' => Hash::make($this->password),
                        'role' => 'pegawai',
                    ]);
                    $emp->user_id = $user->id;
                    $emp->save();
                }

                session()->flash('success', "Data pegawai {$name} berhasil diperbarui.");
            } else {
                $user = User::create([
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $phone.'@ayampotong.com',
                    'password' => Hash::make($this->password),
                    'role' => 'pegawai',
                ]);

                Employee::create([
                    'user_id' => $user->id,
                    'name' => $name,
                    'phone' => $phone,
                    'position' => $pos,
                    'daily_rate' => $rate,
                    'is_active' => true,
                ]);

                session()->flash('success', "Pegawai {$name} berhasil ditambahkan dan akun siap digunakan.");
            }
        });

        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('owner');
        $emp = Employee::findOrFail($id);
        $emp->is_active = ! $emp->is_active;
        $emp->save();

        if (! $emp->is_active && $emp->user) {
            $emp->user->tokens()->delete();
        }

        session()->flash('success', "Status {$emp->name} diubah menjadi ".($emp->is_active ? 'Aktif' : 'Nonaktif'));
    }

    public function delete(int $id): void
    {
        $this->authorize('owner');
        $emp = Employee::findOrFail($id);

        if ($emp->user?->isOwner()) {
            session()->flash('error', 'Akun pemilik (owner) tidak dapat dihapus.');

            return;
        }

        if ($emp->payrolls()->exists()) {
            session()->flash('error', 'Pegawai memiliki riwayat slip gaji dan tidak dapat dihapus.');

            return;
        }

        DB::transaction(function () use ($emp) {
            if ($emp->user_id && $user = User::find($emp->user_id)) {
                $user->tokens()->delete();
                $user->delete();
            }

            $emp->delete();
        });

        session()->flash('success', 'Data pegawai berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'phone', 'password', 'is_active', 'employeeId', 'showFormModal']);
        $this->position = 'Pegawai';
        $this->daily_rate = 100000;
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        $query = Employee::query();
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            });
        }

        $employees = $query->orderBy('name', 'asc')->paginate(15);

        return view('livewire.employees.index', [
            'employees' => $employees,
            'totalEmployees' => Employee::count(),
            'activeEmployees' => Employee::where('is_active', true)->count(),
        ]);
    }
}
