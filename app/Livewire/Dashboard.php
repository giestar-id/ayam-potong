<?php

namespace App\Livewire;

use App\Models\CashFlow;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Sale;
use App\Services\CashFlowService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public string $period = 'month'; // 'today', 'week', 'month', 'custom'
    public string $from = '';
    public string $to = '';

    public function mount(): void
    {
        $this->updatePeriodDates();
    }

    public function updatedPeriod(): void
    {
        $this->updatePeriodDates();
    }

    private function updatePeriodDates(): void
    {
        $today = now();
        if ($this->period === 'today') {
            $this->from = $today->toDateString();
            $this->to = $today->toDateString();
        } elseif ($this->period === 'yesterday') {
            $yesterday = $today->copy()->subDay()->toDateString();
            $this->from = $yesterday;
            $this->to = $yesterday;
        } elseif ($this->period === 'week') {
            $this->from = $today->copy()->startOfWeek()->toDateString();
            $this->to = $today->toDateString();
        } elseif ($this->period === 'month') {
            $this->from = $today->copy()->startOfMonth()->toDateString();
            $this->to = $today->toDateString();
        }
    }

    public function render(CashFlowService $cashFlowService): View
    {
        $data = $cashFlowService->getDashboardSummary($this->from, $this->to);

        return view('livewire.dashboard', [
            'summary' => $data['summary'],
            'chart' => $data['chart'],
            'topProducts' => $data['top_products'] ?? [],
            'recentSales' => $data['recent_sales'],
            'lowStockProducts' => $data['low_stock_products'],
            'activeProductions' => $data['active_productions'],
            'maturingDebts' => $data['maturing_debts'],
        ]);
    }
}
