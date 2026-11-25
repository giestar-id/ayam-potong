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
        'buy_price_per_kg',
        'average_weight_per_bird',
        'live_bird_count',
        'raw_material_cost',
        'additional_cost',
        'total_output_weight_kg',
        'remaining_weight_kg',
        'remaining_bird_count',
        'dead_bird_count',
        'dead_weight_kg',
        'dead_cost',
        'shrinkage_kg',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date:Y-m-d',
            'live_weight_kg' => 'decimal:2',
            'buy_price_per_kg' => 'decimal:2',
            'average_weight_per_bird' => 'decimal:2',
            'live_bird_count' => 'integer',
            'raw_material_cost' => 'decimal:2',
            'additional_cost' => 'decimal:2',
            'total_output_weight_kg' => 'decimal:2',
            'remaining_weight_kg' => 'decimal:2',
            'remaining_bird_count' => 'integer',
            'dead_bird_count' => 'integer',
            'dead_weight_kg' => 'decimal:2',
            'dead_cost' => 'decimal:2',
            'shrinkage_kg' => 'decimal:2',
        ];
    }

    public function outputs()
    {
        return $this->hasMany(ProductionOutput::class);
    }

    public function getAverageWeightPerBirdAttribute(): ?float
    {
        if (isset($this->attributes['average_weight_per_bird']) && (float) $this->attributes['average_weight_per_bird'] > 0) {
            return (float) $this->attributes['average_weight_per_bird'];
        }

        if ((float) ($this->live_bird_count ?? 0) > 0 && (float) ($this->live_weight_kg ?? 0) > 0) {
            return round((float) $this->live_weight_kg / (float) $this->live_bird_count, 2);
        }

        return null;
    }

    public function getEffectiveBuyPricePerKgAttribute(): float
    {
        if ((float) $this->buy_price_per_kg > 0) {
            return (float) $this->buy_price_per_kg;
        }

        if ((float) $this->live_weight_kg > 0 && (float) $this->raw_material_cost > 0) {
            return round((float) $this->raw_material_cost / (float) $this->live_weight_kg, 2);
        }

        return 0.0;
    }

    public function getDeadCostAttribute(): ?float
    {
        if (isset($this->attributes['dead_cost']) && (float) $this->attributes['dead_cost'] > 0) {
            return (float) $this->attributes['dead_cost'];
        }

        if ($this->effective_buy_price_per_kg > 0 && (float) ($this->dead_weight_kg ?? 0) > 0) {
            return round((float) $this->dead_weight_kg * $this->effective_buy_price_per_kg, 2);
        }

        return null;
    }

    public function getUsedWeightKgAttribute(): float
    {
        $live = (float) $this->live_weight_kg;
        $rem = (float) $this->remaining_weight_kg;
        $dead = (float) ($this->dead_weight_kg ?? 0);

        return max(0.0, round($live - $rem - $dead, 2));
    }

    public function getUsedCostAttribute(): float
    {
        return round($this->used_weight_kg * $this->effective_buy_price_per_kg, 2);
    }

    public function getRemainingCostAttribute(): ?float
    {
        if ($this->effective_buy_price_per_kg > 0) {
            return round((float) $this->remaining_weight_kg * $this->effective_buy_price_per_kg, 2);
        }

        return null;
    }

    public function getPosSummary(): array
    {
        $dateStr = $this->production_date ? $this->production_date->toDateString() : now()->toDateString();
        $sales = Sale::whereDate('transaction_date', $dateStr)->get();
        $totalRevenue = (float) $sales->sum('grand_total');
        $saleIds = $sales->pluck('id');

        $soldWeightKg = (float) SaleItem::whereIn('sale_id', $saleIds)
            ->where(function ($q) {
                $q->where('unit', 'kg')->orWhere('unit', 'KG')->orWhereNull('unit');
            })
            ->sum('quantity');

        $liveWeight = (float) $this->live_weight_kg;
        $remainingWeight = (float) $this->remaining_weight_kg;
        $deadWeight = (float) ($this->dead_weight_kg ?? 0);
        $buyPrice = $this->effective_buy_price_per_kg;

        $usedWeightKg = max(0.0, round($liveWeight - $remainingWeight - $deadWeight, 2));
        $usedCost = round($usedWeightKg * $buyPrice, 2);
        $remainingCost = $buyPrice > 0 ? round($remainingWeight * $buyPrice, 2) : 0.0;
        $deadCost = $this->dead_cost ?? 0.0;
        $remainingBirds = (int) ($this->remaining_bird_count ?? 0);
        $deadBirds = (int) ($this->dead_bird_count ?? 0);

        // Penyusutan: modal kotor keluar dikurangi hasil terjual di kasir POS
        $shrinkageKg = max(0.0, round($usedWeightKg - $soldWeightKg, 2));
        $shrinkagePct = $usedWeightKg > 0 ? round(($shrinkageKg / $usedWeightKg) * 100, 2) : 0.0;

        // HPP Daging Terjual: Modal kotor ayam keluar (setelah dikurangi sisa dan mati) dibagi total kg daging yang terjual di kasir
        $hppPerKgSold = $soldWeightKg > 0 ? round($usedCost / $soldWeightKg, 2) : 0.0;

        // Keuntungan: omzet POS dikurangi modal kotor yang terpakai
        $profit = round($totalRevenue - $usedCost, 2);
        $profitMargin = $totalRevenue > 0 ? round(($profit / $totalRevenue) * 100, 1) : 0.0;

        return [
            'sold_weight_kg' => $soldWeightKg,
            'total_revenue' => $totalRevenue,
            'used_weight_kg' => $usedWeightKg,
            'used_cost' => $usedCost,
            'remaining_weight_kg' => $remainingWeight,
            'remaining_bird_count' => $remainingBirds,
            'remaining_birds' => $remainingBirds,
            'dead_bird_count' => $deadBirds,
            'dead_birds' => $deadBirds,
            'dead_weight_kg' => $deadWeight,
            'dead_cost' => $deadCost,
            'average_weight_per_bird' => $this->average_weight_per_bird,
            'remaining_cost' => $remainingCost,
            'shrinkage_kg' => $shrinkageKg,
            'shrinkage_pct' => $shrinkagePct,
            'hpp_per_kg_sold' => $hppPerKgSold,
            'profit' => $profit,
            'profit_margin' => $profitMargin,
            'sales_count' => $sales->count(),
        ];
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
