<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\CashFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected CashFlowService $cashFlowService
    ) {}

    public function summary(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $data = $this->cashFlowService->getDashboardSummary($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
