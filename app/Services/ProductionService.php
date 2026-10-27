<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionOutput;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductionService
{
    public function generateBatchNumber(): string
    {
        $dateStr = now()->format('Ymd');
        $countToday = ProductionBatch::whereDate('created_at', now()->toDateString())->count() + 1;

        return 'BATCH-' . $dateStr . '-' . str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);
    }

    public function createBatch(array $data): ProductionBatch
    {
        if (($data['live_weight_kg'] ?? 0) <= 0) {
            throw new InvalidArgumentException('Berat ayam hidup harus lebih besar dari 0 kg.');
        }

        $batchNumber = $data['batch_number'] ?? $this->generateBatchNumber();
        $date = $data['production_date'] ?? now()->toDateString();

        $batch = ProductionBatch::create([
            'batch_number' => $batchNumber,
            'production_date' => $date,
            'status' => 'active',
            'live_weight_kg' => $data['live_weight_kg'],
            'live_bird_count' => $data['live_bird_count'] ?? null,
            'raw_material_cost' => $data['raw_material_cost'] ?? 0,
            'additional_cost' => $data['additional_cost'] ?? 0,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->invalidateCache();

        return $batch;
    }

    public function updateStage(ProductionBatch $batch, array $data): ProductionBatch
    {
        if ($batch->status !== 'active') {
            throw new InvalidArgumentException('Batch yang sudah selesai atau dibatalkan tidak dapat diubah.');
        }

        $batch->update([
            'additional_cost' => $data['additional_cost'] ?? $batch->additional_cost,
            'notes' => $data['notes'] ?? $batch->notes,
        ]);

        $this->invalidateCache();

        return $batch;
    }

    /**
     * Selesaikan batch produksi ayam potong (PRD 2.1, 2.2, 2.3, 3.2)
     * Output breakdown: array of ['product_id' => int, 'output_qty' => float]
     */
    public function completeBatch(ProductionBatch $batch, array $outputs): ProductionBatch
    {
        if ($batch->status !== 'active') {
            throw new InvalidArgumentException('Hanya batch berstatus aktif yang dapat diselesaikan.');
        }

        if (empty($outputs)) {
            throw new InvalidArgumentException('Rincian hasil timbangan potongan tidak boleh kosong.');
        }

        return DB::transaction(function () use ($batch, $outputs) {
            $batch = ProductionBatch::lockForUpdate()->findOrFail($batch->id);

            $totalOutputWeight = 0.0;
            foreach ($outputs as $item) {
                $qty = (float) ($item['output_qty'] ?? 0);
                if ($qty <= 0) {
                    throw new InvalidArgumentException('Setiap hasil timbangan harus memiliki kuantitas > 0.');
                }
                $totalOutputWeight += $qty;
            }

            $liveWeight = (float) $batch->live_weight_kg;
            $shrinkageKg = max(0.0, round($liveWeight - $totalOutputWeight, 2));

            // PRD 2.2 Rumus HPP Produksi per Kg
            $totalBatchCost = (float) $batch->raw_material_cost + (float) $batch->additional_cost;
            $batchHppPerKg = $totalOutputWeight > 0 ? round($totalBatchCost / $totalOutputWeight, 2) : 0.0;

            // Update masing-masing produk ke stok & hitung modal rata-rata baru (PRD 2.3)
            foreach ($outputs as $item) {
                $productId = $item['product_id'];
                $outputQty = (float) $item['output_qty'];

                $product = Product::lockForUpdate()->findOrFail($productId);
                $currentStock = (float) $product->stock;
                $currentHpp = (float) $product->average_cost;

                $newStock = $currentStock + $outputQty;
                $incomingTotalCost = $outputQty * $batchHppPerKg;

                // Moving Weighted Average Cost
                if ($newStock > 0) {
                    $newHpp = round((($currentStock * $currentHpp) + $incomingTotalCost) / $newStock, 2);
                } else {
                    $newHpp = $batchHppPerKg;
                }

                $product->stock = $newStock;
                $product->average_cost = $newHpp;
                $product->save();

                ProductionOutput::create([
                    'production_batch_id' => $batch->id,
                    'product_id' => $productId,
                    'output_qty' => $outputQty,
                    'allocated_cost_per_unit' => $batchHppPerKg,
                ]);
            }

            // Selesaikan batch
            $batch->total_output_weight_kg = $totalOutputWeight;
            $batch->shrinkage_kg = $shrinkageKg;
            $batch->status = 'completed';
            $batch->save();

            // PRD 3.2 Sinkronisasi Otomatis ke Pengeluaran (CashFlow)
            $date = $batch->production_date ? $batch->production_date->toDateString() : now()->toDateString();

            if ((float) $batch->raw_material_cost > 0) {
                CashFlow::create([
                    'type' => 'expense',
                    'category' => 'production_cost',
                    'reference_type' => ProductionBatch::class,
                    'reference_id' => $batch->id,
                    'transaction_date' => $date,
                    'amount' => $batch->raw_material_cost,
                    'description' => "Bahan baku ayam hidup: Batch {$batch->batch_number} ({$batch->live_weight_kg} kg)",
                ]);
            }

            if ((float) $batch->additional_cost > 0) {
                CashFlow::create([
                    'type' => 'expense',
                    'category' => 'production_cost',
                    'reference_type' => ProductionBatch::class,
                    'reference_id' => $batch->id,
                    'transaction_date' => $date,
                    'amount' => $batch->additional_cost,
                    'description' => "Biaya operasional produksi: Batch {$batch->batch_number}",
                ]);
            }

            $this->invalidateCache();

            return $batch->fresh('outputs.product');
        });
    }

    public function invalidateCache(): void
    {
        Cache::forget('products_catalog_active');
        Cache::forget('dashboard_metrics_' . now()->toDateString());
    }
}
