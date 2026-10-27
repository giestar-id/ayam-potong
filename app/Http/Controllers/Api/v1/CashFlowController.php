<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\CashFlow;
use App\Services\CashFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashFlowController extends Controller
{
    public function __construct(
        protected CashFlowService $cashFlowService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = CashFlow::query();

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('transaction_date', [$request->query('start_date'), $request->query('end_date')]);
        }

        $flows = $query->latest('transaction_date')->latest('id')->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $flows,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|in:sales,debt_payment,stock_purchase,production_cost,payroll,operational_utility,other',
            'amount' => 'required|numeric|gt:0',
            'description' => 'required|string|max:500',
            'transaction_date' => 'nullable|date',
        ]);

        if ($validated['type'] === 'expense') {
            $flow = $this->cashFlowService->createManualExpense(
                $validated['category'],
                (float) $validated['amount'],
                $validated['description'],
                $validated['transaction_date'] ?? null
            );
        } else {
            $flow = $this->cashFlowService->createManualIncome(
                (float) $validated['amount'],
                $validated['description'],
                $validated['transaction_date'] ?? null
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Catatan arus kas berhasil disimpan.',
            'data' => $flow,
        ], 201);
    }
}
