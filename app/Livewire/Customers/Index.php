<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $name = '';
    public string $phone = '';
    public string $address = '';
    public string $notes = '';
    public string $discount_type = 'none'; // 'none', 'percent', 'fixed'
    public string $discount_value = '0';

    public string $search = '';

    #[Locked]
    public ?int $customerId = null;
    public ?int $detailCustomerId = null;
    public bool $showFormModal = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->resetForm();
        $this->showFormModal = false;
    }

    public function edit(int $id): void
    {
        $customer = Customer::findOrFail($id);
        $this->customerId = $id;
        $this->name = $customer->name;
        $this->phone = (string) ($customer->phone ?? '');
        $this->address = (string) ($customer->address ?? '');
        $this->notes = (string) ($customer->notes ?? '');
        $this->discount_type = $customer->discount_type;
        $this->discount_value = (string) $customer->discount_value;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
            'discount_type' => 'required|in:none,percent,fixed',
            'discount_value' => 'required|numeric|min:0',
        ]);

        Customer::updateOrCreate(
            ['id' => $this->customerId],
            [
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'address' => $this->address ?: null,
                'notes' => $this->notes ?: null,
                'discount_type' => $this->discount_type,
                'discount_value' => (float) $this->discount_value,
            ]
        );

        $this->resetForm();
        $this->showFormModal = false;
        session()->flash('success', 'Data pelanggan berhasil disimpan.');
    }

    public function viewDetails(int $id): void
    {
        $this->detailCustomerId = $id;
    }

    public function closeDetails(): void
    {
        $this->detailCustomerId = null;
    }

    public function delete(int $id): void
    {
        $customer = Customer::findOrFail($id);
        if ($customer->sales()->exists()) {
            session()->flash('error', 'Pelanggan memiliki riwayat transaksi dan tidak dapat dihapus.');
            return;
        }

        $customer->delete();
        session()->flash('success', 'Pelanggan berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'phone', 'address', 'notes', 'discount_type', 'discount_value', 'customerId']);
        $this->resetValidation();
    }

    public function render(): View
    {
        $query = Customer::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%");
            });
        }

        $customers = $query->orderBy('name', 'asc')->paginate(15);
        $detailCustomer = $this->detailCustomerId ? Customer::with(['sales.items.product'])->find($this->detailCustomerId) : null;

        return view('livewire.customers.index', [
            'customers' => $customers,
            'detailCustomer' => $detailCustomer,
            'totalCustomers' => Customer::count(),
            'totalActiveDebt' => Customer::get()->sum('active_debt'),
        ]);
    }
}
