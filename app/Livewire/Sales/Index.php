<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Services\TransactionService;
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
    public string $search = '';

    #[Url]
    public string $status = ''; // 'paid', 'partial', 'unpaid'

    #[Url]
    public string $startDate = '';

    #[Url]
    public string $endDate = '';

    public int $perPage = 20;

    // Modal Pelunasan Piutang
    public ?int $selectedSaleId = null;
    public string $debtPayAmount = '';
    public string $debtPayNotes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openDebtPayment(int $saleId): void
    {
        $sale = Sale::findOrFail($saleId);
        $this->selectedSaleId = $sale->id;
        $this->debtPayAmount = (string) $sale->remaining_debt;
        $this->debtPayNotes = '';
        $this->resetErrorBag();
    }

    public function closeDebtPayment(): void
    {
        $this->selectedSaleId = null;
    }

    public function saveDebtPayment(TransactionService $transactionService): void
    {
        $this->validate([
            'debtPayAmount' => 'required|numeric|gt:0',
            'debtPayNotes' => 'nullable|string',
        ], [
            'debtPayAmount.required' => 'Nominal pembayaran piutang wajib diisi.',
            'debtPayAmount.gt' => 'Nominal pembayaran harus lebih besar dari 0.',
        ]);

        $sale = Sale::findOrFail($this->selectedSaleId);

        try {
            $transactionService->payDebt(
                $sale,
                (float) $this->debtPayAmount,
                $this->debtPayNotes ?: null
            );

            session()->flash('success', "Pembayaran piutang nota {$sale->invoice_number} berhasil dicatat dan masuk ke Pemasukan kas.");
            $this->closeDebtPayment();
        } catch (\Exception $e) {
            $this->addError('debtPayAmount', $e->getMessage());
        }
    }

    public function render(): View
    {
        $query = Sale::with(['customer', 'items.product', 'debtPayments']);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                  ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$this->search}%"));
            });
        }

        if ($this->status !== '') {
            $query->where('payment_status', $this->status);
        }

        if ($this->startDate !== '' && $this->endDate !== '') {
            $query->whereBetween('transaction_date', [$this->startDate, $this->endDate]);
        }

        $sales = $query->latest('transaction_date')->latest('id')->paginate($this->perPage);

        return view('livewire.sales.index', [
            'sales' => $sales,
            'totalSalesCount' => Sale::count(),
            'totalRevenue' => Sale::sum('grand_total'),
            'totalPaid' => Sale::sum('paid_amount'),
            'totalRemainingDebt' => Sale::whereIn('payment_status', ['partial', 'unpaid'])->sum('remaining_debt'),
        ]);
    }
}
