<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->decimal('buy_price_per_kg', 15, 2)->default(0)->after('live_weight_kg');
            $table->decimal('remaining_weight_kg', 12, 2)->default(0)->after('total_output_weight_kg');
        });

        // Populate buy_price_per_kg for existing batches if live_weight_kg > 0
        DB::statement('UPDATE production_batches SET buy_price_per_kg = ROUND(raw_material_cost / live_weight_kg, 2) WHERE live_weight_kg > 0 AND raw_material_cost > 0');
    }

    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropColumn(['buy_price_per_kg', 'remaining_weight_kg']);
        });
    }
};
