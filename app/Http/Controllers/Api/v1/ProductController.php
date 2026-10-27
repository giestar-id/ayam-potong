<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $products = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with(['stockPurchases' => fn ($q) => $q->latest()->take(10)])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'name' => 'required|string|max:255',
            'unit' => 'required|in:kg,ekor',
            'stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'average_cost' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $product = $this->productService->createProduct($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan.',
            'data' => $product,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'sku' => 'nullable|string|max:50|unique:products,sku,' . $product->id,
            'name' => 'required|string|max:255',
            'unit' => 'required|in:kg,ekor',
            'min_stock' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $product = $this->productService->updateProduct($product, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui.',
            'data' => $product,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        try {
            $this->productService->deleteProduct($product);

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function restock(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'required|numeric|gt:0',
            'total_cost' => 'nullable|numeric|gt:0',
            'notes' => 'nullable|string',
            'purchase_date' => 'nullable|date',
        ]);

        $quantity = (float) $validated['quantity'];
        $totalCost = isset($validated['total_cost']) && (float) $validated['total_cost'] > 0
            ? (float) $validated['total_cost']
            : round($quantity * ((float) $product->average_cost > 0 ? (float) $product->average_cost : 25000), 2);

        $purchase = $this->productService->restock(
            $product,
            $quantity,
            $totalCost,
            $validated['notes'] ?? null,
            $validated['purchase_date'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Restock berhasil dicatat. Stok dan HPP telah diperbarui.',
            'data' => [
                'purchase' => $purchase,
                'product' => $product->fresh(),
            ],
        ]);
    }
}
