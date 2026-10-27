<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('total_cost', 15, 2);
            $table->decimal('cost_per_unit', 15, 2);
            $table->text('notes')->nullable();
            $table->date('purchase_date');
            $table->timestamps();

            $table->index(['product_id', 'purchase_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_purchases');
    }
};
