<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
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
    public string $position = '';
    public string $daily_rate = '100000';
    public bool $is_active = true;
    public bool $showFormModal = false;

    #[Locked]
    public ?int $employeeId = null;

    public function openCreateModal(): void
    {
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
        $emp = Employee::findOrFail($id);
        $this->employeeId = $emp->id;
        $this->name = $emp->name;
        $this->phone = (string) ($emp->phone ?? '');
        $this->position = (string) ($emp->position ?? '');
        $this->daily_rate = (string) $emp->daily_rate;
        $this->is_active = (bool) $emp->is_active;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'position' => 'nullable|string|max:100',
            'daily_rate' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Nama karyawan wajib diisi.',
            'daily_rate.required' => 'Tarif gaji harian wajib diisi.',
        ]);

        Employee::updateOrCreate(
            ['id' => $this->employeeId],
            [
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'position' => $this->position ?: null,
                'daily_rate' => (float) $this->daily_rate,
                'is_active' => $this->is_active,
            ]
        );

        $this->resetForm();
        session()->flash('success', 'Data karyawan berhasil disimpan.');
    }

    public function toggleActive(int $id): void
    {
        $emp = Employee::findOrFail($id);
        $emp->is_active = ! $emp->is_active;
        $emp->save();
        session()->flash('success', "Status {$emp->name} diubah menjadi " . ($emp->is_active ? 'Aktif' : 'Nonaktif'));
    }

    public function delete(int $id): void
    {
        $emp = Employee::findOrFail($id);
        if ($emp->payrolls()->exists()) {
            session()->flash('error', 'Karyawan memiliki riwayat slip gaji dan tidak dapat dihapus.');
            return;
        }

        $emp->delete();
        session()->flash('success', 'Karyawan berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'phone', 'position', 'daily_rate', 'is_active', 'employeeId', 'showFormModal']);
        $this->daily_rate = '100000';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        $query = Employee::query();
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('position', 'like', "%{$this->search}%");
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
