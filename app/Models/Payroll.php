<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payment_date',
        'period_start',
        'period_end',
        'days_worked',
        'daily_rate_snapshot',
        'total_salary',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'days_worked' => 'decimal:1',
            'daily_rate_snapshot' => 'decimal:2',
            'total_salary' => 'decimal:2',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
