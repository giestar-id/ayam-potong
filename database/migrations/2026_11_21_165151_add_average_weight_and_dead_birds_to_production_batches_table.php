<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->decimal('average_weight_per_bird', 6, 2)->nullable()->after('buy_price_per_kg');
            $table->integer('dead_bird_count')->default(0)->after('remaining_bird_count');
            $table->decimal('dead_weight_kg', 12, 2)->default(0)->after('dead_bird_count');
            $table->decimal('dead_cost', 15, 2)->default(0)->after('dead_weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropColumn([
                'average_weight_per_bird',
                'dead_bird_count',
                'dead_weight_kg',
                'dead_cost',
            ]);
        });
    }
};
