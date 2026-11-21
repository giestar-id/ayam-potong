<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionOutput;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductionService
{
    public function generateBatchNumber(?string $date = null): string
    {
        $d = $date ? Carbon::parse($date) : now();
        $dayNames = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $dayName = $dayNames[$d->format('l')] ?? $d->format('l');
        $dateFormatted = $d->format('Y-m-d');
        $baseName = "{$dayName}, {$dateFormatted}";

        if (ProductionBatch::where('batch_number', $baseName)->exists()) {
            $count = ProductionBatch::where('batch_number', 'like', "{$baseName}%")->count() + 1;

            return "{$baseName} #{$count}";
        }

        return $baseName;
    }

    public function createBatch(array $data): ProductionBatch
    {
        $liveWeight = (float) ($data['live_weight_kg'] ?? 0);
        if ($liveWeight <= 0) {
            throw new InvalidArgumentException('Berat ayam hidup harus lebih besar dari 0 kg.');
        }

        $buyPrice = (float) ($data['buy_price_per_kg'] ?? 0);
        $rawCost = (float) ($data['raw_material_cost'] ?? 0);

        if ($buyPrice > 0 && $rawCost <= 0) {
            $rawCost = round($liveWeight * $buyPrice, 2);
        } elseif ($rawCost > 0 && $buyPrice <= 0) {
            $buyPrice = round($rawCost / $liveWeight, 2);
        }

        $remainingWeight = (float) ($data['remaining_weight_kg'] ?? 0);
        $date = $data['production_date'] ?? now()->toDateString();
        $rawBatchNumber = ! empty($data['batch_number']) ? trim($data['batch_number']) : null;
        $batchNumber = $rawBatchNumber ?: $this->generateBatchNumber($date);

        if (ProductionBatch::where('batch_number', $batchNumber)->exists()) {
            $count = ProductionBatch::where('batch_number', 'like', "{$batchNumber}%")->count() + 1;
            $batchNumber = "{$batchNumber} #{$count}";
        }

        $batch = ProductionBatch::create([
            'batch_number' => $batchNumber,
            'production_date' => $date,
            'status' => 'active',
            'live_weight_kg' => $liveWeight,
            'buy_price_per_kg' => $buyPrice,
            'average_weight_per_bird' => isset($data['average_weight_per_bird']) && (float) $data['average_weight_per_bird'] > 0 ? (float) $data['average_weight_per_bird'] : null,
            'remaining_weight_kg' => $remainingWeight,
            'live_bird_count' => $data['live_bird_count'] ?? null,
            'raw_material_cost' => $rawCost,
            'additional_cost' => $data['additional_cost'] ?? 0,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->invalidateCache();

        return $batch;
    }

    public function updateRemaining(
        ProductionBatch $batch,
        ?float $remainingWeight = null,
        ?int $remainingBirds = null,
        ?int $deadBirds = null,
        ?float $deadWeight = null
    ): ProductionBatch {
        $avgWeight = $batch->average_weight_per_bird;
        $buyPrice = $batch->effective_buy_price_per_kg;

        if ($avgWeight !== null && $avgWeight > 0) {
            if ($remainingBirds !== null) {
                $remainingBirds = max(0, $remainingBirds);
                $remainingWeight = round($remainingBirds * $avgWeight, 2);
            } elseif ($remainingWeight !== null) {
                $remainingWeight = max(0.0, round($remainingWeight, 2));
                $remainingBirds = (int) round($remainingWeight / $avgWeight);
            } else {
                $remainingBirds = $batch->remaining_bird_count ?? 0;
                $remainingWeight = (float) ($batch->remaining_weight_kg ?? 0.0);
            }

            if ($deadBirds !== null) {
                $deadBirds = max(0, $deadBirds);
                $deadWeight = round($deadBirds * $avgWeight, 2);
            } elseif ($deadWeight !== null) {
                $deadWeight = max(0.0, round($deadWeight, 2));
                $deadBirds = (int) round($deadWeight / $avgWeight);
            } else {
                $deadBirds = $batch->dead_bird_count ?? 0;
                $deadWeight = (float) ($batch->dead_weight_kg ?? 0.0);
            }

            $deadCost = ($buyPrice > 0 && $deadWeight > 0) ? round($deadWeight * $buyPrice, 2) : 0.0;
        } else {
            // Owner did not input average weight per bird
            if ($remainingBirds !== null) {
                $remainingBirds = max(0, $remainingBirds);
                $remainingWeight = $remainingWeight !== null ? max(0.0, round($remainingWeight, 2)) : 0.0;
            } elseif ($remainingWeight !== null) {
                $remainingWeight = max(0.0, round($remainingWeight, 2));
                $remainingBirds = $batch->remaining_bird_count ?? 0;
            } else {
                $remainingBirds = $batch->remaining_bird_count ?? 0;
                $remainingWeight = (float) ($batch->remaining_weight_kg ?? 0.0);
            }

            if ($deadBirds !== null) {
                $deadBirds = max(0, $deadBirds);
                $deadWeight = $deadWeight !== null ? max(0.0, round($deadWeight, 2)) : 0.0;
            } elseif ($deadWeight !== null) {
                $deadWeight = max(0.0, round($deadWeight, 2));
                $deadBirds = $batch->dead_bird_count ?? 0;
            } else {
                $deadBirds = $batch->dead_bird_count ?? 0;
                $deadWeight = (float) ($batch->dead_weight_kg ?? 0.0);
            }

            $deadCost = 0.0;
        }

        $batch->update([
            'remaining_bird_count' => $remainingBirds,
            'remaining_weight_kg' => $remainingWeight,
            'dead_bird_count' => $deadBirds,
            'dead_weight_kg' => $deadWeight,
            'dead_cost' => $deadCost,
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
                if ($newStock > 0 && $currentStock > 0) {
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

    public function deleteBatch(ProductionBatch $batch): bool
    {
        return DB::transaction(function () use ($batch) {
            CashFlow::where('reference_type', ProductionBatch::class)
                ->where('reference_id', $batch->id)
                ->delete();

            $batch->outputs()->delete();
            $deleted = $batch->delete();
            $this->invalidateCache();

            return (bool) $deleted;
        });
    }

    public function invalidateCache(): void
    {
        Cache::forget('products_catalog_active');
        Cache::forget('dashboard_metrics_'.now()->toDateString());
    }
}
