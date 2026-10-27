<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayrollService
{
    /**
     * Catat gajian mingguan karyawan (PRD 2.4 & 3.7)
     * Rumus: Total Gaji = Hari Kerja * Tarif Harian
     */
    public function createPayroll(
        Employee $employee,
        float $daysWorked,
        string $periodStart,
        string $periodEnd,
        ?string $notes = null,
        ?string $paymentDate = null
    ): Payroll {
        if ($daysWorked <= 0) {
            throw new InvalidArgumentException('Jumlah hari kerja harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($employee, $daysWorked, $periodStart, $periodEnd, $notes, $paymentDate) {
            $employee = Employee::findOrFail($employee->id);
            $dailyRate = (float) $employee->daily_rate;
            $totalSalary = round($daysWorked * $dailyRate, 2);

            $date = $paymentDate ? date('Y-m-d', strtotime($paymentDate)) : now()->toDateString();

            $payroll = Payroll::create([
                'employee_id' => $employee->id,
                'payment_date' => $date,
                'period_start' => date('Y-m-d', strtotime($periodStart)),
                'period_end' => date('Y-m-d', strtotime($periodEnd)),
                'days_worked' => $daysWorked,
                'daily_rate_snapshot' => $dailyRate,
                'total_salary' => $totalSalary,
                'notes' => $notes,
            ]);

            // PRD 3.7 Integrasi Pengeluaran Otomatis
            CashFlow::create([
                'type' => 'expense',
                'category' => 'payroll',
                'reference_type' => Payroll::class,
                'reference_id' => $payroll->id,
                'transaction_date' => $date,
                'amount' => $totalSalary,
                'description' => "Gaji mingguan {$employee->name} ({$daysWorked} hari kerja @ Rp " . number_format($dailyRate, 0, ',', '.') . ")",
            ]);

            $this->invalidateCache();

            return $payroll->load('employee');
        });
    }

    public function invalidateCache(): void
    {
        Cache::forget('dashboard_metrics_' . now()->toDateString());
    }
}
