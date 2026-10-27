<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'notes',
        'discount_type',
        'discount_value',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
        ];
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function getActiveDebtAttribute(): float
    {
        return (float) $this->sales()
            ->whereIn('payment_status', ['partial', 'unpaid'])
            ->sum('remaining_debt');
    }

    public function getTotalPurchasesAttribute(): float
    {
        return (float) $this->sales()->sum('grand_total');
    }
}
