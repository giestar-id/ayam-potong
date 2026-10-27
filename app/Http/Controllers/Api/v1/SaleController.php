<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Sale::with(['customer', 'items.product', 'debtPayments']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->query('payment_status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('transaction_date', [$request->query('start_date'), $request->query('end_date')]);
        }

        $sales = $query->latest('transaction_date')->latest('id')->paginate($request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $sales,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $sale = Sale::with(['customer', 'user', 'items.product', 'debtPayments'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $sale,
        ]);
    }

    public function payDebt(Request $request, int $id): JsonResponse
    {
        $sale = Sale::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|gt:0',
            'notes' => 'nullable|string',
            'payment_date' => 'nullable|date',
        ]);

        try {
            $payment = $this->transactionService->payDebt(
                $sale,
                (float) $validated['amount'],
                $validated['notes'] ?? null,
                $validated['payment_date'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran piutang berhasil dicatat.',
                'data' => [
                    'payment' => $payment,
                    'sale' => $sale->fresh(['customer', 'debtPayments']),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
