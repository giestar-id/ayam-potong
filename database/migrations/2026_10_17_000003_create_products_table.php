<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable()->unique();
            $table->string('name')->index();
            $table->string('unit')->default('kg'); // 'kg' or 'ekor'
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(5);
            $table->decimal('average_cost', 15, 2)->default(0); // HPP rata-rata per satuan
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['stock', 'min_stock']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
