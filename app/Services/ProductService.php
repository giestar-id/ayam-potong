<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Product;
use App\Models\StockPurchase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductService
{
    public function createProduct(array $data): Product
    {
        $product = Product::create([
            'sku' => $data['sku'] ?? null,
            'name' => $data['name'],
            'unit' => $data['unit'] ?? 'kg',
            'stock' => $data['stock'] ?? 0,
            'min_stock' => $data['min_stock'] ?? 5,
            'average_cost' => $data['average_cost'] ?? 0,
            'selling_price' => $data['selling_price'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->invalidateCache();

        return $product;
    }

    public function updateProduct(Product $product, array $data): Product
    {
        $product->update([
            'sku' => $data['sku'] ?? $product->sku,
            'name' => $data['name'] ?? $product->name,
            'unit' => $data['unit'] ?? $product->unit,
            'min_stock' => $data['min_stock'] ?? $product->min_stock,
            'selling_price' => $data['selling_price'] ?? $product->selling_price,
            'is_active' => $data['is_active'] ?? $product->is_active,
        ]);

        $this->invalidateCache();

        return $product;
    }

    public function deleteProduct(Product $product): bool
    {
        if ($product->saleItems()->exists() || $product->productionOutputs()->exists()) {
            throw new InvalidArgumentException('Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi atau produksi.');
        }

        $deleted = $product->delete();
        $this->invalidateCache();

        return (bool) $deleted;
    }

    /**
     * Catat pembelian stok masuk langsung di menu produk (PRD 2.3 - Moving Weighted Average Cost)
     * Rumus: HPP_baru = ((S_lama * HPP_lama) + C_masuk_total) / (S_lama + S_masuk)
     */
    public function restock(
        Product $product,
        float $quantity,
        float $totalCost,
        ?string $notes = null,
        ?string $purchaseDate = null
    ): StockPurchase {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Kuantitas masuk harus lebih besar dari 0.');
        }
        if ($totalCost <= 0) {
            throw new InvalidArgumentException('Total modal pembelian harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($product, $quantity, $totalCost, $notes, $purchaseDate) {
            $product = Product::lockForUpdate()->findOrFail($product->id);

            $currentStock = (float) $product->stock;
            $currentHpp = (float) $product->average_cost;

            $newStock = $currentStock + $quantity;
            $costPerUnit = round($totalCost / $quantity, 2);

            // PRD 2.3 Moving Weighted Average Cost
            if ($newStock > 0) {
                $newAverageHpp = round((($currentStock * $currentHpp) + $totalCost) / $newStock, 2);
            } else {
                $newAverageHpp = $costPerUnit;
            }

            $product->stock = $newStock;
            $product->average_cost = $newAverageHpp;
            $product->save();

            $date = $purchaseDate ? date('Y-m-d', strtotime($purchaseDate)) : now()->toDateString();

            $purchase = StockPurchase::create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'total_cost' => $totalCost,
                'cost_per_unit' => $costPerUnit,
                'notes' => $notes,
                'purchase_date' => $date,
            ]);

            // PRD 3.3 Integrasi Pengeluaran Otomatis
            CashFlow::create([
                'type' => 'expense',
                'category' => 'stock_purchase',
                'reference_type' => StockPurchase::class,
                'reference_id' => $purchase->id,
                'transaction_date' => $date,
                'amount' => $totalCost,
                'description' => "Belanja stok {$product->name}: {$quantity} {$product->unit} @ Rp " . number_format($costPerUnit, 0, ',', '.'),
            ]);

            $this->invalidateCache();

            return $purchase;
        });
    }

    public function invalidateCache(): void
    {
        Cache::forget('products_catalog_active');
        Cache::forget('dashboard_metrics_' . now()->toDateString());
    }
}
