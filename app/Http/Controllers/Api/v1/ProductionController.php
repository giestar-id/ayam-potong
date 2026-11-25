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

        $batches->each(function (ProductionBatch $b) {
            $b->pos_summary = $b->getPosSummary();
            $b->effective_buy_price_per_kg = $b->effective_buy_price_per_kg;
        });

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $batch = ProductionBatch::with(['outputs.product'])->findOrFail($id);
        $batch->pos_summary = $batch->getPosSummary();
        $batch->effective_buy_price_per_kg = $batch->effective_buy_price_per_kg;

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
            'buy_price_per_kg' => 'nullable|numeric|min:0',
            'raw_material_cost' => 'nullable|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
            'remaining_weight_kg' => 'nullable|numeric|min:0',
            'live_bird_count' => 'nullable|integer|min:0',
            'average_weight_per_bird' => 'nullable|numeric|min:0',
            'additional_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['total_cost']) && empty($validated['raw_material_cost'])) {
            $validated['raw_material_cost'] = $validated['total_cost'];
        }

        // Cegah double batch jika ada request identik dalam selang 5 detik terakhir
        $recentDuplicate = ProductionBatch::where('live_weight_kg', $validated['live_weight_kg'])
            ->where('created_at', '>=', now()->subSeconds(5))
            ->first();

        if ($recentDuplicate) {
            $recentDuplicate->pos_summary = $recentDuplicate->getPosSummary();
            $recentDuplicate->effective_buy_price_per_kg = $recentDuplicate->effective_buy_price_per_kg;

            return response()->json([
                'success' => true,
                'message' => 'Batch produksi sudah dibuat.',
                'data' => $recentDuplicate,
            ], 200);
        }

        try {
            $batch = $this->productionService->createBatch($validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $batch->pos_summary = $batch->getPosSummary();
        $batch->effective_buy_price_per_kg = $batch->effective_buy_price_per_kg;

        return response()->json([
            'success' => true,
            'message' => 'Batch produksi berhasil dibuat.',
            'data' => $batch,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $batch = ProductionBatch::findOrFail($id);

        $validated = $request->validate([
            'batch_number' => 'nullable|string|max:100|unique:production_batches,batch_number,'.$batch->id,
            'production_date' => 'nullable|date',
            'live_weight_kg' => 'required|numeric|gt:0',
            'buy_price_per_kg' => 'nullable|numeric|min:0',
            'raw_material_cost' => 'nullable|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
            'average_weight_per_bird' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['total_cost']) && empty($validated['raw_material_cost'])) {
            $validated['raw_material_cost'] = $validated['total_cost'];
        }

        try {
            $batch = $this->productionService->updateBatch($batch, $validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $batch->pos_summary = $batch->getPosSummary();
        $batch->effective_buy_price_per_kg = $batch->effective_buy_price_per_kg;

        return response()->json([
            'success' => true,
            'message' => 'Batch produksi berhasil diperbarui.',
            'data' => $batch,
        ]);
    }

    public function updateRemaining(Request $request, int $id): JsonResponse
    {
        $batch = ProductionBatch::findOrFail($id);

        $validated = $request->validate([
            'remaining_birds' => 'nullable|integer|min:0',
            'remaining_bird_count' => 'nullable|integer|min:0',
            'remaining_weight_kg' => 'nullable|numeric|min:0',
            'dead_birds' => 'nullable|integer|min:0',
            'dead_bird_count' => 'nullable|integer|min:0',
            'dead_weight_kg' => 'nullable|numeric|min:0',
        ]);

        $birds = isset($validated['remaining_birds']) ? (int) $validated['remaining_birds'] : (isset($validated['remaining_bird_count']) ? (int) $validated['remaining_bird_count'] : null);
        $weight = isset($validated['remaining_weight_kg']) ? (float) $validated['remaining_weight_kg'] : null;

        $deadBirds = isset($validated['dead_birds']) ? (int) $validated['dead_birds'] : (isset($validated['dead_bird_count']) ? (int) $validated['dead_bird_count'] : null);
        $deadWeight = isset($validated['dead_weight_kg']) ? (float) $validated['dead_weight_kg'] : null;

        $batch = $this->productionService->updateRemaining($batch, $weight, $birds, $deadBirds, $deadWeight);
        $batch->pos_summary = $batch->getPosSummary();
        $batch->effective_buy_price_per_kg = $batch->effective_buy_price_per_kg;

        return response()->json([
            'success' => true,
            'message' => 'Sisa ayam batch berhasil diperbarui.',
            'data' => $batch,
        ]);
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

    public function destroy(Request $request, int $id): JsonResponse
    {
        $batch = ProductionBatch::findOrFail($id);

        try {
            $this->productionService->deleteBatch($batch);

            return response()->json([
                'success' => true,
                'message' => 'Batch produksi berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
