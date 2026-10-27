<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Payroll::with('employee');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->query('employee_id'));
        }

        $payrolls = $query->latest('payment_date')->latest('id')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payrolls,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'days_worked' => 'required|numeric|gt:0',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'notes' => 'nullable|string',
            'payment_date' => 'nullable|date',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        $payroll = $this->payrollService->createPayroll(
            $employee,
            (float) $validated['days_worked'],
            $validated['period_start'],
            $validated['period_end'],
            $validated['notes'] ?? null,
            $validated['payment_date'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Gaji karyawan berhasil dihitung dan dicatat sebagai pengeluaran.',
            'data' => $payroll,
        ], 201);
    }
}
