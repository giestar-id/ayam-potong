<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Employee::query();

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $employees = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $employee = Employee::with(['payrolls' => fn ($q) => $q->latest()->take(10)])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'position' => 'nullable|string|max:100',
            'daily_rate' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $employee = Employee::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Karyawan berhasil ditambahkan.',
            'data' => $employee,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'position' => 'nullable|string|max:100',
            'daily_rate' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data karyawan berhasil diperbarui.',
            'data' => $employee,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);

        if ($employee->payrolls()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan memiliki riwayat penggajian dan tidak dapat dihapus.',
            ], 422);
        }

        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Karyawan berhasil dihapus.',
        ]);
    }
}
