<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_batch_id',
        'product_id',
        'output_qty',
        'allocated_cost_per_unit',
    ];

    protected function casts(): array
    {
        return [
            'output_qty' => 'decimal:2',
            'allocated_cost_per_unit' => 'decimal:2',
        ];
    }

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
