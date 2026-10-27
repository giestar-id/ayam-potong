<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BusinessSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_name',
        'address',
        'phone',
        'receipt_footer',
    ];

    public static function current(): self
    {
        try {
            $cached = Cache::get('business_settings_cache');
            if (is_array($cached)) {
                $instance = new self();
                $instance->setRawAttributes($cached, true);
                $instance->exists = true;
                return $instance;
            }
        } catch (\Throwable $e) {
            // fallback gracefully
        }

        $setting = self::firstOrCreate([], [
            'business_name' => 'Ayam Potong Segar',
            'address' => 'Jl. Pasar Unggas No. 12, Jawa Timur',
            'phone' => '0812-3456-7890',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda. Daging ayam segar & higienis!',
        ]);

        try {
            Cache::put('business_settings_cache', $setting->getAttributes(), 86400);
        } catch (\Throwable $e) {
            // ignore cache write failures
        }

        return $setting;
    }

    public static function forgetCache(): void
    {
        try {
            Cache::forget('business_settings_cache');
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
