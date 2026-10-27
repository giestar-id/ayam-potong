<?php

namespace App\Livewire\Cash;

use App\Models\CashFlow;
use App\Services\CashFlowService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $direction = 'Pemasukan'; // 'Pemasukan' or 'Pengeluaran'

    #[Url]
    public string $category = '';

    #[Url]
    public string $startDate = '';

    #[Url]
    public string $endDate = '';

    public int $perPage = 25;

    // Modal Input Manual
    public bool $showManualModal = false;
    public string $manualType = 'expense'; // 'expense' or 'income'
    public string $manualCategory = 'operational_utility';
    public string $manualAmount = '';
    public string $manualDescription = '';
    public string $manualDate = '';

    public function mount(): void
    {
        $this->manualDate = now()->toDateString();
    }

    public function openManualModal(string $type = 'expense'): void
    {
        $this->manualType = $type;
        $this->manualCategory = $type === 'expense' ? 'operational_utility' : 'other';
        $this->manualAmount = '';
        $this->manualDescription = '';
        $this->manualDate = now()->toDateString();
        $this->showManualModal = true;
        $this->resetErrorBag();
    }

    public function closeManualModal(): void
    {
        $this->showManualModal = false;
    }

    public function saveManualEntry(CashFlowService $cashFlowService): void
    {
        $this->validate([
            'manualAmount' => 'required|numeric|gt:0',
            'manualDescription' => 'required|string|max:500',
            'manualDate' => 'required|date',
        ], [
            'manualAmount.required' => 'Nominal transaksi wajib diisi.',
            'manualAmount.gt' => 'Nominal harus lebih besar dari 0.',
            'manualDescription.required' => 'Keterangan pengeluaran/pemasukan wajib diisi.',
        ]);

        try {
            if ($this->manualType === 'expense') {
                $cashFlowService->createManualExpense(
                    $this->manualCategory,
                    (float) $this->manualAmount,
                    $this->manualDescription,
                    $this->manualDate
                );
                session()->flash('success', 'Pengeluaran operasional berhasil dicatat.');
            } else {
                $cashFlowService->createManualIncome(
                    (float) $this->manualAmount,
                    $this->manualDescription,
                    $this->manualDate
                );
                session()->flash('success', 'Pemasukan lain-lain berhasil dicatat.');
            }

            $this->closeManualModal();
        } catch (\Exception $e) {
            $this->addError('manualAmount', $e->getMessage());
        }
    }

    public function render(): View
    {
        $type = $this->direction === 'Pemasukan' ? 'income' : 'expense';
        $query = CashFlow::where('type', $type);

        if ($this->category !== '') {
            $query->where('category', $this->category);
        }

        if ($this->startDate !== '' && $this->endDate !== '') {
            $query->whereBetween('transaction_date', [$this->startDate, $this->endDate]);
        }

        $flows = $query->latest('transaction_date')->latest('id')->paginate($this->perPage);

        $totalIncome = CashFlow::where('type', 'income')->sum('amount');
        $totalExpense = CashFlow::where('type', 'expense')->sum('amount');

        return view('livewire.cash.index', [
            'flows' => $flows,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netCash' => $totalIncome - $totalExpense,
        ]);
    }
}
