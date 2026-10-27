<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Employee $employee;
    protected PayrollService $payrollService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'name' => 'Owner',
            'email' => 'owner@ayampotong.com',
            'role' => 'owner',
        ]);

        $this->employee = Employee::create([
            'name' => 'Bambang Jagal',
            'position' => 'butcher',
            'phone' => '081298765432',
            'daily_rate' => 125000, // Rp 125.000 / hari
            'is_active' => true,
        ]);

        $this->payrollService = app(PayrollService::class);
    }

    public function test_payroll_calculates_total_amount_from_worked_days_and_daily_rate(): void
    {
        // 6 days worked in a week * 125,000 = 750,000
        $payroll = $this->payrollService->createPayroll(
            $this->employee,
            6,
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
            'Gaji mingguan potong ayam'
        );

        $this->assertEquals(6, $payroll->days_worked);
        $this->assertEquals(125000, (float) $payroll->daily_rate_snapshot);
        $this->assertEquals(750000, (float) $payroll->total_salary);

        // Verify cash flow expense recorded automatically
        $this->assertDatabaseHas('cash_flows', [
            'type' => 'expense',
            'category' => 'payroll',
            'amount' => 750000,
        ]);
    }

    public function test_livewire_pos_payroll_cart_checkout(): void
    {
        $employee2 = Employee::create([
            'name' => 'Santoso Penimbang',
            'position' => 'weighing',
            'phone' => '081298765433',
            'daily_rate' => 100000,
            'is_active' => true,
        ]);

        \Livewire\Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Payrolls\Index::class)
            ->call('addToCart', $this->employee->id)
            ->call('addToCart', $employee2->id)
            ->set('cart.' . $this->employee->id . '.days', 6)
            ->set('cart.' . $employee2->id . '.days', 5)
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertSet('cart', []);

        // 6 * 125,000 = 750,000
        $this->assertDatabaseHas('payrolls', [
            'employee_id' => $this->employee->id,
            'total_salary' => 750000,
        ]);

        // 5 * 100,000 = 500,000
        $this->assertDatabaseHas('payrolls', [
            'employee_id' => $employee2->id,
            'total_salary' => 500000,
        ]);

        // Total 2 cash flows for payroll expense
        $this->assertEquals(2, \App\Models\CashFlow::where('category', 'payroll')->count());
    }
}
