<?php

namespace App\Livewire\Productions;

use App\Models\ProductionBatch;
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

    public function render(): View
    {
        $query = ProductionBatch::with(['outputs.product']);

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        $batches = $query->latest('production_date')->latest('id')->paginate($this->perPage);

        return view('livewire.productions.index', [
            'batches' => $batches,
            'totalBatches' => ProductionBatch::count(),
            'activeBatches' => ProductionBatch::where('status', 'active')->count(),
            'totalLiveWeight' => ProductionBatch::where('status', 'completed')->sum('live_weight_kg'),
            'totalOutputWeight' => ProductionBatch::where('status', 'completed')->sum('total_output_weight_kg'),
        ]);
    }
}
