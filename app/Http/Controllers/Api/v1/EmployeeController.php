<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pemilik (owner) yang memiliki akses untuk mengelola pegawai.',
            ], 403);
        }

        $query = Employee::with('user');

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $employees = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $request->user()?->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pemilik (owner) yang memiliki akses untuk mengelola pegawai.',
            ], 403);
        }

        $employee = Employee::with(['user', 'payrolls' => fn ($q) => $q->latest()->take(10)])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()?->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pemilik (owner) yang memiliki akses untuk mengelola pegawai.',
            ], 403);
        }

        // Normalisasi format jika dikirim teks berformat mata uang / titik ribuan
        if ($request->has('daily_rate') && is_string($request->input('daily_rate'))) {
            $raw = preg_replace('/[^\d]/', '', (string) $request->input('daily_rate'));
            $request->merge(['daily_rate' => $raw !== '' ? (float) $raw : 0]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30|unique:users,phone',
            'password' => 'required|string|min:4',
            'position' => 'nullable|string|max:255',
            'daily_rate' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama pegawai wajib diisi.',
            'phone.unique' => 'Nomor HP sudah terdaftar untuk pengguna lain.',
            'password.required' => 'Kata sandi wajib diisi untuk akun pegawai.',
            'password.min' => 'Kata sandi minimal 4 karakter.',
            'daily_rate.numeric' => 'Gaji harian harus berupa nominal angka.',
            'daily_rate.min' => 'Gaji harian tidak boleh negatif.',
        ]);

        $phone = ! empty($validated['phone']) ? trim($validated['phone']) : null;
        $email = $phone ? $phone.'@ayampotong.com' : Str::slug($validated['name']).'_'.Str::random(5).'@ayampotong.com';
        $password = $validated['password'];
        $dailyRate = isset($validated['daily_rate']) ? (float) $validated['daily_rate'] : 0;
        $position = ! empty($validated['position']) ? trim($validated['position']) : 'Pegawai';

        $employee = DB::transaction(function () use ($validated, $phone, $email, $password, $dailyRate, $position, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'phone' => $phone,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'pegawai',
            ]);

            return Employee::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'phone' => $phone,
                'position' => $position,
                'daily_rate' => $dailyRate,
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pegawai berhasil ditambahkan dan akun siap digunakan.',
            'data' => $employee->load('user'),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (! $request->user()?->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pemilik (owner) yang memiliki akses untuk mengelola pegawai.',
            ], 403);
        }

        $employee = Employee::findOrFail($id);

        // Normalisasi format jika dikirim teks berformat mata uang / titik ribuan
        if ($request->has('daily_rate') && is_string($request->input('daily_rate'))) {
            $raw = preg_replace('/[^\d]/', '', (string) $request->input('daily_rate'));
            $request->merge(['daily_rate' => $raw !== '' ? (float) $raw : 0]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($employee->user_id)],
            'password' => 'nullable|string|min:4',
            'position' => 'nullable|string|max:255',
            'daily_rate' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Nama pegawai wajib diisi.',
            'phone.unique' => 'Nomor HP sudah terdaftar untuk pengguna lain.',
            'password.min' => 'Kata sandi minimal 4 karakter.',
            'daily_rate.numeric' => 'Gaji harian harus berupa nominal angka.',
            'daily_rate.min' => 'Gaji harian tidak boleh negatif.',
        ]);

        $phone = ! empty($validated['phone']) ? trim($validated['phone']) : null;

        DB::transaction(function () use ($employee, $validated, $phone, $request) {
            $updateData = [
                'name' => $validated['name'],
                'phone' => $phone,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $employee->is_active,
            ];

            if ($request->has('position')) {
                $updateData['position'] = trim($validated['position'] ?? 'Pegawai');
            }
            if ($request->has('daily_rate')) {
                $updateData['daily_rate'] = (float) ($validated['daily_rate'] ?? 0);
            }

            $employee->update($updateData);

            if ($employee->user_id && $user = User::find($employee->user_id)) {
                $user->name = $validated['name'];
                $user->phone = $phone;
                if ($phone) {
                    $user->email = $phone.'@ayampotong.com';
                }
                if (! empty($validated['password'])) {
                    $user->password = Hash::make($validated['password']);
                    $user->tokens()->delete();
                }
                $user->save();

                if (! $employee->is_active) {
                    $user->tokens()->delete();
                }
            } elseif (! empty($validated['password'])) {
                $user = User::create([
                    'name' => $validated['name'],
                    'phone' => $phone,
                    'email' => $phone.'@ayampotong.com',
                    'password' => Hash::make($validated['password']),
                    'role' => 'pegawai',
                ]);
                $employee->user_id = $user->id;
                $employee->save();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Data dan akun pegawai berhasil diperbarui.',
            'data' => $employee->fresh()->load('user'),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (! $request->user()?->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pemilik (owner) yang memiliki akses untuk mengelola pegawai.',
            ], 403);
        }

        $employee = Employee::findOrFail($id);

        if ($employee->user?->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun pemilik (owner) tidak dapat dihapus.',
            ], 422);
        }

        if ($employee->payrolls()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai memiliki riwayat penggajian dan tidak dapat dihapus.',
            ], 422);
        }

        DB::transaction(function () use ($employee) {
            if ($employee->user_id && $user = User::find($employee->user_id)) {
                $user->tokens()->delete();
                $user->delete();
            }

            $employee->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Pegawai berhasil dihapus.',
        ]);
    }
}
