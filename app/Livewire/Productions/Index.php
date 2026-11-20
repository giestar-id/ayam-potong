<?php

namespace App\Livewire\Productions;

use App\Models\ProductionBatch;
use App\Services\ProductionService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public int $perPage = 15;

    public array $remainingInputs = [];

    // Modal Update Sisa
    public ?int $selectedBatchId = null;

    public string $selectedBatchNumber = '';

    public float $selectedBatchLiveWeight = 0;

    public string $selectedBatchRemaining = '0';

    public function openUpdateRemaining(int $batchId): void
    {
        $batch = ProductionBatch::findOrFail($batchId);
        $this->selectedBatchId = $batch->id;
        $this->selectedBatchNumber = $batch->batch_number;
        $this->selectedBatchLiveWeight = (float) $batch->live_weight_kg;
        $this->selectedBatchRemaining = (string) ((float) $batch->remaining_weight_kg);
        $this->resetErrorBag();
    }

    public function closeUpdateRemaining(): void
    {
        $this->selectedBatchId = null;
    }

    public function saveRemaining(ProductionService $productionService): void
    {
        $this->validate([
            'selectedBatchRemaining' => 'required|numeric|min:0',
        ], [
            'selectedBatchRemaining.required' => 'Sisa ayam wajib diisi.',
            'selectedBatchRemaining.numeric' => 'Sisa ayam harus berupa angka valid.',
            'selectedBatchRemaining.min' => 'Sisa ayam tidak boleh bernilai negatif.',
        ]);

        $batch = ProductionBatch::findOrFail($this->selectedBatchId);
        $weight = (float) $this->selectedBatchRemaining;
        $productionService->updateRemaining($batch, $weight);

        session()->flash('success', "Sisa ayam batch {$batch->batch_number} berhasil diperbarui menjadi {$weight} kg.");
        $this->closeUpdateRemaining();
    }

    public function updateRemaining(int $batchId, ProductionService $productionService): void
    {
        $weight = isset($this->remainingInputs[$batchId]) ? (float) $this->remainingInputs[$batchId] : null;
        if ($weight === null || $weight < 0) {
            session()->flash('error', 'Kuantitas sisa ayam (kg) harus angka valid lebih besar atau sama dengan 0.');

            return;
        }

        $batch = ProductionBatch::findOrFail($batchId);
        $productionService->updateRemaining($batch, $weight);

        session()->flash('success', "Sisa ayam batch {$batch->batch_number} berhasil diperbarui menjadi {$weight} kg.");
    }

    public function render(): View
    {
        $query = ProductionBatch::with(['outputs.product']);

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        $batches = $query->latest('production_date')->latest('id')->paginate($this->perPage);

        foreach ($batches as $b) {
            if (! isset($this->remainingInputs[$b->id])) {
                $this->remainingInputs[$b->id] = (string) ((float) $b->remaining_weight_kg);
            }
        }

        $todayBatch = ProductionBatch::whereDate('production_date', now()->toDateString())->first();
        $todaySummary = $todayBatch ? $todayBatch->getPosSummary() : null;

        return view('livewire.productions.index', [
            'batches' => $batches,
            'totalBatches' => ProductionBatch::count(),
            'activeBatches' => ProductionBatch::where('status', 'active')->count(),
            'totalLiveWeight' => ProductionBatch::sum('live_weight_kg'),
            'totalRemainingWeight' => ProductionBatch::where('status', 'active')->sum('remaining_weight_kg'),
            'todaySummary' => $todaySummary,
        ]);
    }
}
