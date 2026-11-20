<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pemilik (owner) yang memiliki akses ke modul penggajian.',
            ], 403);
        }

        $query = Payroll::with('employee');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->query('employee_id'));
        }

        if ($request->has('page')) {
            $payrolls = $query->latest('payment_date')->latest('id')->paginate(20);
        } else {
            $payrolls = $query->latest('payment_date')->latest('id')->take(100)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $payrolls,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()?->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pemilik (owner) yang memiliki akses untuk memproses penggajian.',
            ], 403);
        }

        // Support bulk checkout (array of items)
        if ($request->has('items') && is_array($request->items)) {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.employee_id' => 'required|exists:employees,id',
                'items.*.days_worked' => 'required|numeric|gt:0|max:31',
                'period_start' => 'required|date',
                'period_end' => 'required|date|after_or_equal:period_start',
                'notes' => 'nullable|string',
                'payment_date' => 'nullable|date',
            ]);

            $paymentDate = ! empty($validated['payment_date']) ? $validated['payment_date'] : now()->toDateString();

            $createdPayrolls = DB::transaction(function () use ($validated, $paymentDate) {
                $payrolls = [];
                foreach ($validated['items'] as $item) {
                    $employee = Employee::findOrFail($item['employee_id']);
                    $payrolls[] = $this->payrollService->createPayroll(
                        $employee,
                        (float) $item['days_worked'],
                        $validated['period_start'],
                        $validated['period_end'],
                        $validated['notes'] ?? null,
                        $paymentDate
                    );
                }

                return $payrolls;
            });

            return response()->json([
                'success' => true,
                'message' => count($createdPayrolls).' gaji karyawan berhasil dibayar dan dicatat.',
                'data' => $createdPayrolls,
            ], 201);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'days_worked' => 'required|numeric|gt:0|max:31',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'notes' => 'nullable|string',
            'payment_date' => 'nullable|date',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $paymentDate = ! empty($validated['payment_date']) ? $validated['payment_date'] : now()->toDateString();

        $payroll = $this->payrollService->createPayroll(
            $employee,
            (float) $validated['days_worked'],
            $validated['period_start'],
            $validated['period_end'],
            $validated['notes'] ?? null,
            $paymentDate
        );

        return response()->json([
            'success' => true,
            'message' => 'Gaji karyawan berhasil dihitung dan dicatat sebagai pengeluaran.',
            'data' => $payroll,
        ], 201);
    }
}
