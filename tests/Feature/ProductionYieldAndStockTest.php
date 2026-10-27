<?php

namespace Tests\Feature;

use App\Models\CashFlow;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Services\ProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionYieldAndStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_yield_shrinkage_stock_and_expense_sync(): void
    {
        $service = app(ProductionService::class);

        // Siapkan master produk potongan
        $sayap = Product::create(['name' => 'Sayap Ayam', 'unit' => 'kg', 'stock' => 0, 'min_stock' => 5, 'average_cost' => 0, 'selling_price' => 30000]);
        $paha = Product::create(['name' => 'Paha Ayam', 'unit' => 'kg', 'stock' => 0, 'min_stock' => 5, 'average_cost' => 0, 'selling_price' => 38000]);
        $dada = Product::create(['name' => 'Dada Ayam', 'unit' => 'kg', 'stock' => 0, 'min_stock' => 5, 'average_cost' => 0, 'selling_price' => 45000]);
        $utuh = Product::create(['name' => 'Ayam Utuh', 'unit' => 'kg', 'stock' => 0, 'min_stock' => 5, 'average_cost' => 0, 'selling_price' => 35000]);

        // Buat batch 500 kg ayam hidup, biaya bahan baku Rp 11.000.000, biaya produksi Rp 400.000
        $batch = $service->createBatch([
            'live_weight_kg' => 500.0,
            'live_bird_count' => 250,
            'raw_material_cost' => 11000000.0,
            'additional_cost' => 400000.0,
            'production_date' => now()->toDateString(),
        ]);

        $this->assertEquals('active', $batch->status);

        // Input hasil timbangan bersih total 380 kg:
        // Sayap: 50 kg, Paha: 120 kg, Dada: 150 kg, Utuh: 60 kg = 380 kg
        $outputs = [
            ['product_id' => $sayap->id, 'output_qty' => 50.0],
            ['product_id' => $paha->id, 'output_qty' => 120.0],
            ['product_id' => $dada->id, 'output_qty' => 150.0],
            ['product_id' => $utuh->id, 'output_qty' => 60.0],
        ];

        $completedBatch = $service->completeBatch($batch, $outputs);

        // 1. Verifikasi Yield & Shrinkage (PRD 2.1)
        $this->assertEquals('completed', $completedBatch->status);
        $this->assertEquals(380.0, (float) $completedBatch->total_output_weight_kg);
        $this->assertEquals(120.0, (float) $completedBatch->shrinkage_kg); // 500 - 380 = 120 kg susut
        $this->assertEquals(76.0, $completedBatch->yield_percentage);      // (380 / 500) * 100 = 76%

        // 2. Verifikasi Stok Produk Bertambah Akurat (PRD 3.2)
        $this->assertEquals(50.0, (float) $sayap->fresh()->stock);
        $this->assertEquals(120.0, (float) $paha->fresh()->stock);
        $this->assertEquals(150.0, (float) $dada->fresh()->stock);
        $this->assertEquals(60.0, (float) $utuh->fresh()->stock);

        // HPP Batch per kg = (11.000.000 + 400.000) / 380 = 11.400.000 / 380 = 30.000 / kg
        $this->assertEquals(30000.0, (float) $sayap->fresh()->average_cost);
        $this->assertEquals(30000.0, (float) $dada->fresh()->average_cost);

        // 3. Verifikasi Pengeluaran Tercatat di CashFlows (PRD 3.2)
        $this->assertDatabaseHas('cash_flows', [
            'type' => 'expense',
            'category' => 'production_cost',
            'amount' => 11000000.0,
        ]);
        $this->assertDatabaseHas('cash_flows', [
            'type' => 'expense',
            'category' => 'production_cost',
            'amount' => 400000.0,
        ]);
    }
}
