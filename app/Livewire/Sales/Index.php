<?php

namespace App\Livewire\Sales;

use App\Models\ProductionBatch;
use App\Models\Sale;
use App\Models\SaleItem;
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
                    ->orWhere('notes', 'like', "%{$this->search}%")
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

        // Kalkulasi Ringkasan Finansial Hari Ini
        $todayStr = now()->toDateString();
        $todaySales = Sale::whereDate('transaction_date', $todayStr)->get();
        $todayRevenue = (float) $todaySales->sum('grand_total');
        $todaySoldKg = (float) SaleItem::whereIn('sale_id', $todaySales->pluck('id'))->sum('quantity');

        $todayBatches = ProductionBatch::whereDate('production_date', $todayStr)->get();
        if ($todayBatches->isEmpty()) {
            $todayBatches = ProductionBatch::latest('production_date')->take(1)->get();
        }

        $usedBatchKg = 0.0;
        $usedBatchCost = 0.0;
        $remainingBatchKg = 0.0;
        $remainingBatchCost = 0.0;

        foreach ($todayBatches as $tb) {
            $usedBatchKg += $tb->used_weight_kg;
            $usedBatchCost += $tb->used_cost;
            $remainingBatchKg += (float) $tb->remaining_weight_kg;
            $remainingBatchCost += $tb->remaining_cost;
        }

        $profitToday = round($todayRevenue - $usedBatchCost, 2);

        return view('livewire.sales.index', [
            'sales' => $sales,
            'totalSalesCount' => Sale::count(),
            'totalRevenue' => Sale::sum('grand_total'),
            'totalPaid' => Sale::sum('paid_amount'),
            'totalRemainingDebt' => (float) Sale::where('remaining_debt', '>', 0)->sum('remaining_debt'),
            'todayRevenue' => $todayRevenue,
            'todaySoldKg' => $todaySoldKg,
            'usedBatchKg' => $usedBatchKg,
            'usedBatchCost' => $usedBatchCost,
            'remainingBatchKg' => $remainingBatchKg,
            'remainingBatchCost' => $remainingBatchCost,
            'profitToday' => $profitToday,
        ]);
    }
}
