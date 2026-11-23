<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $identifier = trim($request->email);
        $lowerId = strtolower($identifier);
        $user = User::whereRaw('LOWER(email) = ?', [$lowerId])
            ->orWhere('phone', $identifier)
            ->orWhereRaw('LOWER(name) = ?', [$lowerId])
            ->first();

        if (! $user && in_array($lowerId, ['owner', 'admin', strtolower(BusinessSetting::current()->business_name)])) {
            $user = User::where('role', 'owner')->first();
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Nomor HP / Email atau kata sandi yang Anda masukkan salah.'],
            ]);
        }

        if ($user->employee && ! $user->employee->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun pegawai Anda sedang dinonaktifkan. Silakan hubungi pemilik gerai.'],
            ]);
        }

        $token = $user->createToken('mobile-pos-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ]);
    }
}
