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

    public string $total_cost = '';

    public string $buy_price_per_kg = '';

    public string $notes = '';

    public function mount(ProductionService $productionService): void
    {
        $this->production_date = now()->toDateString();
        $this->batch_number = $productionService->generateBatchNumber();
    }

    public function updatedTotalCost(): void
    {
        $this->calculateBuyPricePerKg();
    }

    public function updatedLiveWeightKg(): void
    {
        $this->calculateBuyPricePerKg();
    }

    protected function calculateBuyPricePerKg(): void
    {
        $weight = (float) $this->live_weight_kg;
        $total = (float) $this->total_cost;

        if ($weight > 0 && $total > 0) {
            $this->buy_price_per_kg = (string) round($total / $weight, 2);
        } else {
            $this->buy_price_per_kg = '';
        }
    }

    public function getCalculatedBuyPricePerKgProperty(): float
    {
        $weight = (float) $this->live_weight_kg;
        $total = (float) $this->total_cost;

        if ($weight > 0 && $total > 0) {
            return round($total / $weight, 2);
        }

        return 0.0;
    }

    public function getEstimatedCostProperty(): float
    {
        return (float) $this->total_cost;
    }

    public function save(ProductionService $productionService): mixed
    {
        $this->production_date = now()->toDateString();

        $this->validate([
            'batch_number' => 'required|string|unique:production_batches,batch_number',
            'production_date' => 'nullable|date',
            'live_weight_kg' => 'required|numeric|gt:0',
            'total_cost' => 'required|numeric|gt:0',
            'notes' => 'nullable|string',
        ], [
            'live_weight_kg.required' => 'Jumlah kilo ayam wajib diisi.',
            'live_weight_kg.gt' => 'Jumlah kilo ayam harus lebih besar dari 0 kg.',
            'total_cost.required' => 'Total modal pembelian ayam wajib diisi.',
            'total_cost.gt' => 'Total modal pembelian ayam harus lebih besar dari Rp 0.',
        ]);

        $weight = (float) $this->live_weight_kg;
        $total = (float) $this->total_cost;
        $pricePerKg = round($total / $weight, 2);

        $batch = $productionService->createBatch([
            'batch_number' => $this->batch_number,
            'production_date' => $this->production_date,
            'live_weight_kg' => $weight,
            'buy_price_per_kg' => $pricePerKg,
            'raw_material_cost' => $total,
            'notes' => $this->notes ?: null,
        ]);

        session()->flash('success', "Batch produksi {$batch->batch_number} berhasil dibuat ({$batch->live_weight_kg} kg @ Rp ".number_format($batch->buy_price_per_kg, 0, ',', '.').'/kg).');

        return redirect()->route('productions.show', $batch->id);
    }

    public function render(): View
    {
        return view('livewire.productions.form');
    }
}
