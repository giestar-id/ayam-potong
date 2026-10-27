<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('days_worked', 5, 1);
            $table->decimal('daily_rate_snapshot', 15, 2);
            $table->decimal('total_salary', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
