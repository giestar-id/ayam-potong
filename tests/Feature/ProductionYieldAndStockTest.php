<?php

namespace Tests\Feature;

use App\Livewire\Productions\Form as ProductionForm;
use App\Livewire\Productions\Index;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_production_index_update_remaining_modal(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $service = app(ProductionService::class);

        $batch = $service->createBatch([
            'live_weight_kg' => 300.0,
            'live_bird_count' => 150,
            'buy_price_per_kg' => 24000.0,
            'raw_material_cost' => 7200000.0,
            'production_date' => now()->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee($batch->batch_number)
            ->call('openUpdateRemaining', $batch->id)
            ->assertSet('selectedBatchId', $batch->id)
            ->assertSet('selectedBatchNumber', $batch->batch_number)
            ->set('selectedBatchRemaining', '20')
            ->set('selectedBatchDead', '5')
            ->call('saveRemaining')
            ->assertSet('selectedBatchId', null)
            ->assertHasNoErrors();

        $batch->refresh();
        $this->assertEquals(20, (int) $batch->remaining_bird_count);
        $this->assertEquals(40.0, (float) $batch->remaining_weight_kg);
        $this->assertEquals(5, (int) $batch->dead_bird_count);
        $this->assertEquals(10.0, (float) $batch->dead_weight_kg);
        $this->assertEquals(240000.0, (float) $batch->dead_cost);
        $this->assertEquals(250.0, (float) $batch->used_weight_kg); // 300 - 40 - 10 = 250
    }

    public function test_production_form_creates_batch_with_total_cost_and_auto_calculates_price_per_kg(): void
    {
        $user = User::factory()->create(['role' => 'owner']);

        Livewire::actingAs($user)
            ->test(ProductionForm::class)
            ->set('batch_number', 'BATCH-TEST-TOTAL-COST-01')
            ->set('production_date', now()->toDateString())
            ->set('live_weight_kg', '200')
            ->set('total_cost', '4800000')
            ->set('average_weight_per_bird', '1.8')
            ->assertSet('calculatedBuyPricePerKg', 24000.0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('production_batches', [
            'batch_number' => 'BATCH-TEST-TOTAL-COST-01',
            'live_weight_kg' => 200.0,
            'raw_material_cost' => 4800000.0,
            'buy_price_per_kg' => 24000.0,
            'average_weight_per_bird' => 1.8,
        ]);
    }

    public function test_cannot_create_two_batches_on_same_date_even_with_different_names(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $today = now()->toDateString();

        $firstResponse = $this->actingAs($user, 'sanctum')->postJson('/api/v1/productions', [
            'batch_number' => 'Batch Pagi',
            'production_date' => $today,
            'live_weight_kg' => 100,
            'raw_material_cost' => 2400000,
        ]);
        $firstResponse->assertStatus(201);

        $secondResponse = $this->actingAs($user, 'sanctum')->postJson('/api/v1/productions', [
            'batch_number' => 'Batch Sore Berbeda Nama',
            'production_date' => $today,
            'live_weight_kg' => 150,
            'raw_material_cost' => 3600000,
        ]);
        $secondResponse->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseCount('production_batches', 1);
    }
}
