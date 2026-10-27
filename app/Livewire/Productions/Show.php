<?php

namespace App\Livewire\Productions;

use App\Models\Product;
use App\Models\ProductionBatch;
use App\Services\ProductionService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    #[Locked]
    public int $batchId;

    public string $additional_cost = '0';
    public string $notes = '';

    // Array of outputs to submit: [['product_id' => int, 'output_qty' => string]]
    public array $breakdown = [];

    public function mount(int $id): void
    {
        $batch = ProductionBatch::with(['outputs.product'])->findOrFail($id);
        $this->batchId = $batch->id;
        $this->additional_cost = (string) $batch->additional_cost;
        $this->notes = (string) ($batch->notes ?? '');

        if ($batch->status === 'active') {
            // Inisialisasi daftar produk aktif untuk memudahkan kasir/operator menginput timbangan
            $products = Product::where('is_active', true)->orderBy('name', 'asc')->get();
            $this->breakdown = [];
            foreach ($products as $p) {
                $this->breakdown[] = [
                    'product_id' => $p->id,
                    'product_name' => $p->name,
                    'unit' => $p->unit,
                    'output_qty' => '',
                ];
            }
        }
    }

    public function updateAdditionalCost(ProductionService $productionService): void
    {
        $batch = ProductionBatch::findOrFail($this->batchId);
        $productionService->updateStage($batch, [
            'additional_cost' => (float) ($this->additional_cost ?: 0),
            'notes' => $this->notes ?: null,
        ]);

        session()->flash('success', 'Biaya operasional tambahan berhasil diperbarui.');
    }

    public function completeProduction(ProductionService $productionService): mixed
    {
        $batch = ProductionBatch::findOrFail($this->batchId);

        // Filter out empty outputs
        $validOutputs = [];
        foreach ($this->breakdown as $item) {
            $qty = (float) ($item['output_qty'] ?? 0);
            if ($qty > 0) {
                $validOutputs[] = [
                    'product_id' => (int) $item['product_id'],
                    'output_qty' => $qty,
                ];
            }
        }

        if (empty($validOutputs)) {
            session()->flash('error', 'Minimal satu item hasil timbangan potongan harus diisi dengan kuantitas > 0.');
            return null;
        }

        try {
            // Simpan biaya tambahan terbaru terlebih dahulu jika ada
            $productionService->updateStage($batch, [
                'additional_cost' => (float) ($this->additional_cost ?: 0),
                'notes' => $this->notes ?: null,
            ]);

            $batch = $productionService->completeBatch($batch, $validOutputs);

            session()->flash('success', "Batch {$batch->batch_number} berhasil diselesaikan. Stok produk bertambah dan biaya telah tercatat.");
            return redirect()->route('productions.show', $batch->id);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            return null;
        }
    }

    public function render(): View
    {
        $batch = ProductionBatch::with(['outputs.product'])->findOrFail($this->batchId);

        // Kalkulasi Live Preview di UI
        $totalCleanWeight = 0.0;
        foreach ($this->breakdown as $item) {
            $totalCleanWeight += (float) ($item['output_qty'] ?? 0);
        }

        $liveWeight = (float) $batch->live_weight_kg;
        $shrinkageKg = max(0.0, round($liveWeight - $totalCleanWeight, 2));
        $yieldPct = $liveWeight > 0 ? round(($totalCleanWeight / $liveWeight) * 100, 2) : 0.0;
        $shrinkagePct = $liveWeight > 0 ? round(($shrinkageKg / $liveWeight) * 100, 2) : 0.0;

        $totalBatchCost = (float) $batch->raw_material_cost + (float) ($this->additional_cost ?: 0);
        $batchHppPerKg = $totalCleanWeight > 0 ? round($totalBatchCost / $totalCleanWeight, 2) : 0.0;

        return view('livewire.productions.show', [
            'batch' => $batch,
            'totalCleanWeight' => $totalCleanWeight,
            'shrinkageKg' => $shrinkageKg,
            'yieldPct' => $yieldPct,
            'shrinkagePct' => $shrinkagePct,
            'totalBatchCost' => $totalBatchCost,
            'batchHppPerKg' => $batchHppPerKg,
        ]);
    }
}
