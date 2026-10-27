<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->date('production_date');
            $table->string('status')->default('active'); // 'active', 'completed', 'cancelled'
            $table->decimal('live_weight_kg', 12, 2);
            $table->integer('live_bird_count')->nullable();
            $table->decimal('raw_material_cost', 15, 2)->default(0);
            $table->decimal('additional_cost', 15, 2)->default(0);
            $table->decimal('total_output_weight_kg', 12, 2)->default(0);
            $table->decimal('shrinkage_kg', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'production_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
