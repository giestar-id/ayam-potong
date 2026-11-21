<?php

namespace App\Livewire\Productions;

use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Sale;
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

    public string $remaining_birds = '0';

    public string $remaining_weight_kg = '0';

    public string $additional_cost = '0';

    public string $notes = '';

    // Array of outputs to submit: [['product_id' => int, 'output_qty' => string]]
    public array $breakdown = [];

    public function mount(int $id): void
    {
        $batch = ProductionBatch::with(['outputs.product'])->findOrFail($id);
        $this->batchId = $batch->id;
        $this->remaining_birds = (string) ((int) ($batch->remaining_bird_count ?? round($batch->remaining_weight_kg / max(0.1, $batch->average_weight_per_bird))));
        $this->remaining_weight_kg = (string) ((float) $batch->remaining_weight_kg);
        $this->additional_cost = (string) $batch->additional_cost;
        $this->notes = (string) ($batch->notes ?? '');

        if ($batch->status === 'active') {
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

    public function updateRemaining(ProductionService $productionService): void
    {
        $birds = (int) $this->remaining_birds;
        if ($birds < 0) {
            session()->flash('error', 'Kuantitas sisa ayam (ekor) harus angka bulat lebih besar atau sama dengan 0.');

            return;
        }

        $batch = ProductionBatch::findOrFail($this->batchId);
        $productionService->updateRemaining($batch, null, $birds);
        $this->remaining_weight_kg = (string) ((float) $batch->fresh()->remaining_weight_kg);

        session()->flash('success', "Sisa ayam berhasil diperbarui menjadi {$birds} ekor. Perhitungan susut dan keuntungan otomatis diperbarui.");
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

    public function deleteBatch(ProductionService $productionService): mixed
    {
        $batch = ProductionBatch::findOrFail($this->batchId);
        $batchNumber = $batch->batch_number;

        $productionService->deleteBatch($batch);

        session()->flash('success', "Batch {$batchNumber} berhasil dihapus.");

        return redirect()->route('productions.index');
    }

    public function render(): View
    {
        $batch = ProductionBatch::with(['outputs.product'])->findOrFail($this->batchId);
        $posSummary = $batch->getPosSummary();

        $daySales = Sale::whereDate('transaction_date', $batch->production_date)
            ->with(['customer', 'items.product'])
            ->latest('id')
            ->get();

        $totalCleanWeight = 0.0;
        foreach ($this->breakdown as $item) {
            $totalCleanWeight += (float) ($item['output_qty'] ?? 0);
        }

        return view('livewire.productions.show', [
            'batch' => $batch,
            'posSummary' => $posSummary,
            'daySales' => $daySales,
            'totalCleanWeight' => $totalCleanWeight,
        ]);
    }
}
