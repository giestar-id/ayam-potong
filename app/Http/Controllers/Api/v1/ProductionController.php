<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\ProductionBatch;
use App\Services\ProductionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function __construct(
        protected ProductionService $productionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = ProductionBatch::with(['outputs.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $batches = $query->latest('production_date')->latest('id')->get();

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $batch = ProductionBatch::with(['outputs.product'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $batch,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'batch_number' => 'nullable|string|unique:production_batches,batch_number',
            'production_date' => 'nullable|date',
            'live_weight_kg' => 'required|numeric|gt:0',
            'live_bird_count' => 'nullable|integer|min:0',
            'raw_material_cost' => 'required|numeric|min:0',
            'additional_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $batch = $this->productionService->createBatch($validated);

        return response()->json([
            'success' => true,
            'message' => 'Batch produksi berhasil dibuat.',
            'data' => $batch,
        ], 201);
    }

    public function updateStage(Request $request, int $id): JsonResponse
    {
        $batch = ProductionBatch::findOrFail($id);

        $validated = $request->validate([
            'additional_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $batch = $this->productionService->updateStage($batch, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Tahap produksi diperbarui.',
            'data' => $batch,
        ]);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $batch = ProductionBatch::findOrFail($id);

        $validated = $request->validate([
            'outputs' => 'required|array|min:1',
            'outputs.*.product_id' => 'required|exists:products,id',
            'outputs.*.output_qty' => 'required|numeric|gt:0',
        ]);

        try {
            $batch = $this->productionService->completeBatch($batch, $validated['outputs']);

            return response()->json([
                'success' => true,
                'message' => 'Batch produksi selesai. Hasil potongan telah ditambahkan ke stok dan biaya tercatat.',
                'data' => $batch,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
