<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_number',
        'production_date',
        'status',
        'live_weight_kg',
        'live_bird_count',
        'raw_material_cost',
        'additional_cost',
        'total_output_weight_kg',
        'shrinkage_kg',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'live_weight_kg' => 'decimal:2',
            'live_bird_count' => 'integer',
            'raw_material_cost' => 'decimal:2',
            'additional_cost' => 'decimal:2',
            'total_output_weight_kg' => 'decimal:2',
            'shrinkage_kg' => 'decimal:2',
        ];
    }

    public function outputs()
    {
        return $this->hasMany(ProductionOutput::class);
    }

    public function getYieldPercentageAttribute(): float
    {
        if ((float) $this->live_weight_kg <= 0) {
            return 0.0;
        }

        return round(((float) $this->total_output_weight_kg / (float) $this->live_weight_kg) * 100, 2);
    }

    public function getTotalCostAttribute(): float
    {
        return (float) $this->raw_material_cost + (float) $this->additional_cost;
    }

    public function getCostPerKgAttribute(): float
    {
        if ((float) $this->total_output_weight_kg <= 0) {
            return 0.0;
        }

        return round($this->total_cost / (float) $this->total_output_weight_kg, 2);
    }
}
