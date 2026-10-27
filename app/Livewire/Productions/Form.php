<?php

namespace App\Livewire\Productions;

use App\Services\ProductionService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public string $batch_number = '';
    public string $production_date = '';
    public string $live_weight_kg = '';
    public string $live_bird_count = '';
    public string $raw_material_cost = '';
    public string $additional_cost = '0';
    public string $notes = '';

    public function mount(ProductionService $productionService): void
    {
        $this->production_date = now()->toDateString();
        $this->batch_number = $productionService->generateBatchNumber();
    }

    public function save(ProductionService $productionService): mixed
    {
        $this->validate([
            'batch_number' => 'required|string|unique:production_batches,batch_number',
            'production_date' => 'required|date',
            'live_weight_kg' => 'required|numeric|gt:0',
            'live_bird_count' => 'nullable|integer|min:0',
            'raw_material_cost' => 'required|numeric|min:0',
            'additional_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ], [
            'live_weight_kg.required' => 'Berat ayam hidup wajib diisi.',
            'live_weight_kg.gt' => 'Berat ayam hidup harus lebih besar dari 0 kg.',
            'raw_material_cost.required' => 'Total biaya pembelian ayam hidup wajib diisi.',
        ]);

        $batch = $productionService->createBatch([
            'batch_number' => $this->batch_number,
            'production_date' => $this->production_date,
            'live_weight_kg' => (float) $this->live_weight_kg,
            'live_bird_count' => $this->live_bird_count !== '' ? (int) $this->live_bird_count : null,
            'raw_material_cost' => (float) $this->raw_material_cost,
            'additional_cost' => (float) ($this->additional_cost ?: 0),
            'notes' => $this->notes ?: null,
        ]);

        session()->flash('success', "Batch produksi {$batch->batch_number} berhasil dibuat. Silakan lakukan proses pemotongan dan input hasil timbangan.");

        return redirect()->route('productions.show', $batch->id);
    }

    public function render(): View
    {
        return view('livewire.productions.form');
    }
}
