<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'user_id',
        'transaction_date',
        'subtotal',
        'discount_type',
        'discount_amount',
        'grand_total',
        'paid_amount',
        'remaining_debt',
        'payment_status',
        'due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_debt' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function debtPayments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function getChangeAmountAttribute(): float
    {
        if ($this->payment_status === 'paid' && (float) $this->paid_amount > (float) $this->grand_total) {
            return (float) $this->paid_amount - (float) $this->grand_total;
        }

        return 0.0;
    }
}
