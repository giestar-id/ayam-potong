<?php

namespace Tests\Unit;

use App\Models\CashFlow;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAverageCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_weighted_average_cost_formula(): void
    {
        $service = app(ProductService::class);

        // Buat produk dengan sisa stok 10 kg dan HPP lama Rp 25.000
        $product = Product::create([
            'sku' => 'AYM-DADA-TEST',
            'name' => 'Dada Ayam Fillet',
            'unit' => 'kg',
            'stock' => 10.0,
            'min_stock' => 5.0,
            'average_cost' => 25000.0,
            'selling_price' => 35000.0,
            'is_active' => true,
        ]);

        // Belanja stok baru: 15 kg dengan total modal Rp 450.000 (Rp 30.000/kg)
        // Rumus PRD 2.3: ((10 * 25.000) + 450.000) / (10 + 15) = (250.000 + 450.000) / 25 = 700.000 / 25 = 28.000
        $purchase = $service->restock($product, 15.0, 450000.0, 'Belanja stok supplier A');

        $product->refresh();

        $this->assertEquals(25.0, (float) $product->stock);
        $this->assertEquals(28000.0, (float) $product->average_cost);
        $this->assertEquals(30000.0, (float) $purchase->cost_per_unit);

        // Verifikasi pengeluaran otomatis tercatat di cash_flows (PRD 3.3)
        $this->assertDatabaseHas('cash_flows', [
            'type' => 'expense',
            'category' => 'stock_purchase',
            'amount' => 450000.0,
        ]);
    }
}
