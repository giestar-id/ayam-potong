<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Sale;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashFlowService
{
    /**
     * Input pengeluaran operasional manual (Listrik, Gas, Plastik, Es Batu, Transportasi, dll.)
     */
    public function createManualExpense(
        string $category,
        float $amount,
        string $description,
        ?string $date = null
    ): CashFlow {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal pengeluaran harus lebih besar dari 0.');
        }

        $transactionDate = $date ? date('Y-m-d', strtotime($date)) : now()->toDateString();

        $entry = CashFlow::create([
            'type' => 'expense',
            'category' => $category,
            'transaction_date' => $transactionDate,
            'amount' => $amount,
            'description' => $description,
        ]);

        $this->invalidateCache();

        return $entry;
    }

    /**
     * Input pemasukan lain-lain secara manual
     */
    public function createManualIncome(
        float $amount,
        string $description,
        ?string $date = null
    ): CashFlow {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal pemasukan harus lebih besar dari 0.');
        }

        $transactionDate = $date ? date('Y-m-d', strtotime($date)) : now()->toDateString();

        $entry = CashFlow::create([
            'type' => 'income',
            'category' => 'other',
            'transaction_date' => $transactionDate,
            'amount' => $amount,
            'description' => $description,
        ]);

        $this->invalidateCache();

        return $entry;
    }

    /**
     * PRD 3.1 & 5.1 / 5.2 Dashboard Summary terpadu dengan Laravel Local Cache
     */
    public function getDashboardSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = 'dashboard_metrics_' . ($startDate ?? 'default') . '_' . ($endDate ?? 'default');

        $metrics = Cache::remember($cacheKey, 15, function () use ($startDate, $endDate) {
            $cashQuery = CashFlow::query();
            $salesQuery = Sale::query();

            if ($startDate && $endDate) {
                $start = substr($startDate, 0, 10) . ' 00:00:00';
                $end = substr($endDate, 0, 10) . ' 23:59:59';
                $cashQuery->whereBetween('transaction_date', [$start, $end]);
                $salesQuery->whereBetween('transaction_date', [$start, $end]);
            } elseif ($startDate) {
                $start = substr($startDate, 0, 10) . ' 00:00:00';
                $cashQuery->where('transaction_date', '>=', $start);
                $salesQuery->where('transaction_date', '>=', $start);
            } elseif ($endDate) {
                $end = substr($endDate, 0, 10) . ' 23:59:59';
                $cashQuery->where('transaction_date', '<=', $end);
                $salesQuery->where('transaction_date', '<=', $end);
            }

            $totalIncome = (float) (clone $cashQuery)->where('type', 'income')->sum('amount');
            $totalExpense = (float) (clone $cashQuery)->where('type', 'expense')->sum('amount');
            $netCash = $totalIncome - $totalExpense;

            $totalDebt = (float) Sale::whereIn('payment_status', ['partial', 'unpaid'])->sum('remaining_debt');
            $salesCount = $salesQuery->count();

            // Grafik Arus Kas 7 Hari Terakhir
            $plotDays = [];
            $incomeSeries = [];
            $expenseSeries = [];
            $chartDates = [];

            for ($i = 6; $i >= 0; $i--) {
                $d = now()->subDays($i)->toDateString();
                $lbl = date('d M', strtotime($d));
                $chartDates[] = $lbl;

                $income = (float) CashFlow::where('type', 'income')
                    ->whereBetween('transaction_date', [$d . ' 00:00:00', $d . ' 23:59:59'])
                    ->sum('amount');

                $expense = (float) CashFlow::where('type', 'expense')
                    ->whereBetween('transaction_date', [$d . ' 00:00:00', $d . ' 23:59:59'])
                    ->sum('amount');

                $incomeSeries[] = $income;
                $expenseSeries[] = $expense;

                $plotDays[] = [
                    'date' => $d,
                    'label' => $lbl,
                    'in' => $income,
                    'out' => $expense,
                ];
            }

            return [
                'summary' => [
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense,
                    'net_cash' => $netCash,
                    'total_debt' => $totalDebt,
                    'sales_count' => $salesCount,
                ],
                'chart' => [
                    'labels' => $chartDates,
                    'incomes' => $incomeSeries,
                    'expenses' => $expenseSeries,
                    'days' => $plotDays,
                ],
            ];
        });

        // Produk terlaris (Top Selling Products)
        $salesItemSums = \App\Models\SaleItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_qty, SUM(subtotal) as total_sales')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $topProducts = Product::where('is_active', true)
            ->get()
            ->map(function ($product) use ($salesItemSums) {
                $sold = $salesItemSums->get($product->id);
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'unit' => $product->unit,
                    'selling_price' => (float) $product->selling_price,
                    'total_qty' => $sold ? (float) $sold->total_qty : 0.0,
                    'total_sales' => $sold ? (float) $sold->total_sales : 0.0,
                ];
            })
            ->sortByDesc('total_qty')
            ->take(6)
            ->values()
            ->toArray();

        // 5-10 Transaksi terbaru (live query to avoid serialization issues)
        $recentSales = Sale::with('customer')
            ->latest('transaction_date')
            ->latest('id')
            ->take(8)
            ->get();

        // Peringatan stok menipis (Stok <= Min Stok)
        $lowStockProducts = Product::where('is_active', true)
            ->whereRaw('stock <= min_stock')
            ->orderBy('stock', 'asc')
            ->take(10)
            ->get();

        // Produksi Aktif (Status active)
        $activeProductions = ProductionBatch::where('status', 'active')
            ->latest('production_date')
            ->get();

        // Piutang Jatuh Tempo (H-3 atau sudah lewat jatuh tempo)
        $maturingDebts = Sale::with('customer')
            ->whereIn('payment_status', ['partial', 'unpaid'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->addDays(3)->toDateString())
            ->orderBy('due_date', 'asc')
            ->take(10)
            ->get();

        return [
            'summary' => $metrics['summary'] ?? [
                'total_income' => 0.0,
                'total_expense' => 0.0,
                'net_cash' => 0.0,
                'total_debt' => 0.0,
            ],
            'chart' => $metrics['chart'] ?? [
                'labels' => [],
                'incomes' => [],
                'expenses' => [],
                'days' => [],
            ],
            'top_products' => $topProducts,
            'recent_sales' => $recentSales,
            'low_stock_products' => $lowStockProducts,
            'active_productions' => $activeProductions,
            'maturing_debts' => $maturingDebts,
        ];
    }

    public function invalidateCache(): void
    {
        Cache::flush();
    }
}
