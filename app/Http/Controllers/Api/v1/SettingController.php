<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = BusinessSetting::current();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        if (! $request->user()?->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pemilik (owner) yang memiliki akses untuk mengubah pengaturan usaha.',
            ], 403);
        }

        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'receipt_footer' => 'nullable|string',
        ]);

        $settings = BusinessSetting::firstOrCreate([]);
        $settings->update($validated);
        BusinessSetting::forgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan usaha berhasil diperbarui.',
            'data' => $settings,
        ]);
    }
}
