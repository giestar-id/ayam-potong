<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Sale;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'transaction_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'discount_type' => 'nullable|in:none,percent,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $sale = $this->transactionService->createSale($validated, $request->user());
            $settings = BusinessSetting::current();

            $receipt = [
                'business_name' => $settings->business_name,
                'address' => $settings->address,
                'phone' => $settings->phone,
                'invoice_number' => $sale->invoice_number,
                'transaction_date' => $sale->transaction_date->translatedFormat('d F Y'),
                'cashier' => $sale->user?->name ?? 'Kasir',
                'customer' => $sale->customer?->name ?? 'Pelanggan Umum',
                'items' => $sale->items->map(fn ($item) => [
                    'name' => $item->product->name,
                    'qty' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                    'notes' => $item->notes,
                ]),
                'subtotal' => (float) $sale->subtotal,
                'discount' => (float) $sale->discount_amount,
                'grand_total' => (float) $sale->grand_total,
                'paid_amount' => (float) $sale->paid_amount,
                'change' => (float) $sale->change_amount,
                'remaining_debt' => (float) $sale->remaining_debt,
                'payment_status' => $sale->payment_status,
                'due_date' => $sale->due_date ? $sale->due_date->translatedFormat('d F Y') : null,
                'receipt_footer' => $settings->receipt_footer,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan.',
                'data' => [
                    'sale' => $sale,
                    'receipt' => $receipt,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function receipt(int $id): JsonResponse
    {
        $sale = Sale::with(['items.product', 'customer', 'user'])->findOrFail($id);
        $settings = BusinessSetting::current();

        $receipt = [
            'business_name' => $settings->business_name,
            'address' => $settings->address,
            'phone' => $settings->phone,
            'invoice_number' => $sale->invoice_number,
            'transaction_date' => $sale->transaction_date->translatedFormat('d F Y'),
            'cashier' => $sale->user?->name ?? 'Kasir',
            'customer' => $sale->customer?->name ?? 'Pelanggan Umum',
            'items' => $sale->items->map(fn ($item) => [
                'name' => $item->product->name,
                'qty' => (float) $item->quantity,
                'unit' => $item->unit,
                'price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'notes' => $item->notes,
            ]),
            'subtotal' => (float) $sale->subtotal,
            'discount' => (float) $sale->discount_amount,
            'grand_total' => (float) $sale->grand_total,
            'paid_amount' => (float) $sale->paid_amount,
            'change' => (float) $sale->change_amount,
            'remaining_debt' => (float) $sale->remaining_debt,
            'payment_status' => $sale->payment_status,
            'due_date' => $sale->due_date ? $sale->due_date->translatedFormat('d F Y') : null,
            'receipt_footer' => $settings->receipt_footer,
        ];

        return response()->json([
            'success' => true,
            'data' => $receipt,
        ]);
    }
}
