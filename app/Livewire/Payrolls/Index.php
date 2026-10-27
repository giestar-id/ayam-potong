<?php

namespace App\Livewire\Payrolls;

use App\Models\CashFlow;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    /** @var array<int, array{days: float|int|string}> */
    public array $cart = [];

    public string $period_start = '';
    public string $period_end = '';
    public string $payment_date = '';
    public string $notes = '';

    // Filters for employee card list
    public string $employeeSearch = '';

    // Filters for history table
    public string $historyEmployeeFilter = '';
    public string $historyDateFrom = '';
    public string $historyDateTo = '';

    public int $perPage = 20;

    public function mount(): void
    {
        $this->period_start = now()->startOfWeek()->toDateString();
        $this->period_end = now()->endOfWeek()->toDateString();
        $this->payment_date = now()->toDateString();
    }

    public function toggleCart(int $employeeId): void
    {
        if (isset($this->cart[$employeeId])) {
            unset($this->cart[$employeeId]);
        } else {
            $employee = Employee::where('is_active', true)->find($employeeId);
            if ($employee) {
                $this->cart[$employeeId] = ['days' => 6];
            }
        }
    }

    public function addToCart(int $employeeId): void
    {
        if (! isset($this->cart[$employeeId])) {
            $employee = Employee::where('is_active', true)->find($employeeId);
            if ($employee) {
                $this->cart[$employeeId] = ['days' => 6];
            }
        }
    }

    public function removeItem(int $employeeId): void
    {
        unset($this->cart[$employeeId]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    public function selectAllEmployees(): void
    {
        $activeEmployees = Employee::where('is_active', true)->get();
        foreach ($activeEmployees as $emp) {
            if (! isset($this->cart[$emp->id])) {
                $this->cart[$emp->id] = ['days' => 6];
            }
        }
    }

    public function incrementDays(int $employeeId, float $amount = 1): void
    {
        if (isset($this->cart[$employeeId])) {
            $current = (float) ($this->cart[$employeeId]['days'] ?? 0);
            $this->cart[$employeeId]['days'] = min(31, $current + $amount);
        }
    }

    public function decrementDays(int $employeeId, float $amount = 1): void
    {
        if (isset($this->cart[$employeeId])) {
            $current = (float) ($this->cart[$employeeId]['days'] ?? 0);
            $this->cart[$employeeId]['days'] = max(0.5, $current - $amount);
        }
    }

    public function checkout(PayrollService $payrollService): void
    {
        $this->validate([
            'cart' => 'required|array|min:1',
            'cart.*.days' => 'required|numeric|gt:0|max:31',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ], [
            'cart.required' => 'Pilih minimal satu karyawan untuk pembayaran gaji.',
            'cart.min' => 'Pilih minimal satu karyawan untuk pembayaran gaji.',
            'cart.*.days.gt' => 'Hari kerja harus lebih dari 0.',
            'period_start.required' => 'Periode awal kerja wajib ditentukan.',
            'period_end.required' => 'Periode akhir kerja wajib ditentukan.',
            'period_end.after_or_equal' => 'Periode akhir kerja harus sama atau setelah periode awal.',
        ]);

        $employeeIds = array_keys($this->cart);
        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');

        if ($employees->isEmpty()) {
            session()->flash('error', 'Karyawan yang dipilih tidak ditemukan.');
            return;
        }

        $totalPaid = 0;
        $count = 0;

        try {
            DB::transaction(function () use ($employees, $payrollService, &$totalPaid, &$count) {
                foreach ($this->cart as $empId => $data) {
                    $employee = $employees->get($empId);
                    if (! $employee) {
                        continue;
                    }

                    $daysWorked = (float) ($data['days'] ?? 6);
                    if ($daysWorked <= 0) {
                        continue;
                    }

                    $payroll = $payrollService->createPayroll(
                        $employee,
                        $daysWorked,
                        $this->period_start,
                        $this->period_end,
                        $this->notes ?: null,
                        $this->payment_date
                    );

                    $totalPaid += (float) $payroll->total_salary;
                    $count++;
                }
            });

            $this->clearCart();
            $this->notes = '';

            session()->flash('success', "Gaji {$count} pekerja berhasil dibayar (Total Rp " . number_format($totalPaid, 0, ',', '.') . ") dan otomatis tercatat pada Buku Kas!");
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal memproses penggajian: ' . $e->getMessage());
        }
    }

    public function deletePayroll(int $id): void
    {
        $payroll = Payroll::findOrFail($id);

        DB::transaction(function () use ($payroll) {
            // Hapus cash flow pengeluaran yang terhubung
            CashFlow::where('reference_type', Payroll::class)
                ->where('reference_id', $payroll->id)
                ->delete();

            $payroll->delete();
        });

        session()->flash('success', 'Catatan slip gaji dan pengeluaran kas terkait berhasil dihapus.');
    }

    public function render(): View
    {
        // 1. Kartu Karyawan Siap Bayar
        $availableEmployees = Employee::where('is_active', true)
            ->when($this->employeeSearch !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', "%{$this->employeeSearch}%")
                        ->orWhere('position', 'like', "%{$this->employeeSearch}%");
                });
            })
            ->orderBy('name', 'asc')
            ->get();

        // 2. Kalkulasi Keranjang Gaji
        $cartLines = [];
        $cartTotal = 0;
        if (! empty($this->cart)) {
            $cartEmployees = Employee::whereIn('id', array_keys($this->cart))->get()->keyBy('id');
            foreach ($this->cart as $empId => $item) {
                $emp = $cartEmployees->get($empId);
                if ($emp) {
                    $days = (float) ($item['days'] ?? 1);
                    $rate = (float) $emp->daily_rate;
                    $subtotal = round($days * $rate, 2);
                    $cartLines[$empId] = [
                        'employee' => $emp,
                        'name' => $emp->name,
                        'position' => $emp->position ?? 'Pekerja',
                        'rate' => $rate,
                        'days' => $days,
                        'amount' => $subtotal,
                    ];
                    $cartTotal += $subtotal;
                }
            }
        }

        // 3. Riwayat Slip Gaji
        $payrollsQuery = Payroll::with('employee')->latest('payment_date')->latest('id');

        if ($this->historyEmployeeFilter !== '') {
            $payrollsQuery->where('employee_id', $this->historyEmployeeFilter);
        }
        if ($this->historyDateFrom !== '') {
            $payrollsQuery->whereDate('payment_date', '>=', $this->historyDateFrom);
        }
        if ($this->historyDateTo !== '') {
            $payrollsQuery->whereDate('payment_date', '<=', $this->historyDateTo);
        }

        $payrolls = $payrollsQuery->paginate($this->perPage);

        // 4. Stats
        $totalPayrollPaid = (float) Payroll::sum('total_salary');
        $thisMonthPayroll = (float) Payroll::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('total_salary');
        $activeEmployeesCount = Employee::where('is_active', true)->count();

        return view('livewire.payrolls.index', [
            'availableEmployees' => $availableEmployees,
            'cartLines' => $cartLines,
            'cartTotal' => $cartTotal,
            'payrolls' => $payrolls,
            'totalPayrollPaid' => $totalPayrollPaid,
            'thisMonthPayroll' => $thisMonthPayroll,
            'activeEmployeesCount' => $activeEmployeesCount,
            'allEmployees' => Employee::orderBy('name', 'asc')->get(),
        ]);
    }
}
