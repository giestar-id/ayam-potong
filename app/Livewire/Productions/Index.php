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

    public int $selectedBatchLiveBirdCount = 0;

    public float $selectedBatchAvgWeight = 2.0;

    public float $selectedBatchBuyPrice = 0;

    public string $selectedBatchRemaining = '0';

    public function openUpdateRemaining(int $batchId): void
    {
        $batch = ProductionBatch::findOrFail($batchId);
        $this->selectedBatchId = $batch->id;
        $this->selectedBatchNumber = $batch->batch_number;
        $this->selectedBatchLiveWeight = (float) $batch->live_weight_kg;
        $this->selectedBatchLiveBirdCount = (int) ($batch->live_bird_count ?? 0);
        $this->selectedBatchAvgWeight = $batch->average_weight_per_bird;
        $this->selectedBatchBuyPrice = $batch->effective_buy_price_per_kg;
        $this->selectedBatchRemaining = (string) ((int) ($batch->remaining_bird_count ?? round($batch->remaining_weight_kg / max(0.1, $batch->average_weight_per_bird))));
        $this->resetErrorBag();
    }

    public function closeUpdateRemaining(): void
    {
        $this->selectedBatchId = null;
    }

    public function saveRemaining(ProductionService $productionService): void
    {
        $this->validate([
            'selectedBatchRemaining' => 'required|integer|min:0',
        ], [
            'selectedBatchRemaining.required' => 'Sisa ayam wajib diisi.',
            'selectedBatchRemaining.integer' => 'Sisa ayam harus berupa angka bulat ekor.',
            'selectedBatchRemaining.min' => 'Sisa ayam tidak boleh bernilai negatif.',
        ]);

        $batch = ProductionBatch::findOrFail($this->selectedBatchId);
        $birds = (int) $this->selectedBatchRemaining;
        $productionService->updateRemaining($batch, null, $birds);

        session()->flash('success', "Sisa ayam batch {$batch->batch_number} berhasil diperbarui menjadi {$birds} ekor.");
        $this->closeUpdateRemaining();
    }

    public function updateRemaining(int $batchId, ProductionService $productionService): void
    {
        $birds = isset($this->remainingInputs[$batchId]) ? (int) $this->remainingInputs[$batchId] : null;
        if ($birds === null || $birds < 0) {
            session()->flash('error', 'Kuantitas sisa ayam (ekor) harus angka bulat lebih besar atau sama dengan 0.');

            return;
        }

        $batch = ProductionBatch::findOrFail($batchId);
        $productionService->updateRemaining($batch, null, $birds);

        session()->flash('success', "Sisa ayam batch {$batch->batch_number} berhasil diperbarui menjadi {$birds} ekor.");
    }

    public function deleteBatch(int $batchId, ProductionService $productionService): void
    {
        $batch = ProductionBatch::findOrFail($batchId);
        $batchNumber = $batch->batch_number;

        try {
            $productionService->deleteBatch($batch);
            session()->flash('success', "Batch produksi {$batchNumber} berhasil dihapus.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
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
                $this->remainingInputs[$b->id] = (string) ((int) ($b->remaining_bird_count ?? round($b->remaining_weight_kg / max(0.1, $b->average_weight_per_bird))));
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
