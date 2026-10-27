<?php

use App\Http\Controllers\SaleReceiptController;
use App\Http\Middleware\EnsureOwner;
use App\Livewire\Auth\Login;
use App\Livewire\Cash\Index as CashIndex;
use App\Livewire\Customers\Index as CustomerIndex;
use App\Livewire\Dashboard;
use App\Livewire\Employees\Index as EmployeeIndex;
use App\Livewire\Payrolls\Index as PayrollIndex;
use App\Livewire\Productions\Form as ProductionForm;
use App\Livewire\Productions\Index as ProductionIndex;
use App\Livewire\Productions\Show as ProductionShow;
use App\Livewire\Products\Form as ProductForm;
use App\Livewire\Products\Index as ProductIndex;
use App\Livewire\Sales\Index as SaleIndex;
use App\Livewire\Sales\Pos;
use App\Livewire\Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Authenticated Routes
Route::middleware(['auth', 'auth.session', EnsureOwner::class])->group(function () {
    // Dashboard
    Route::get('/', Dashboard::class)->name('dashboard');

    // Logout
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');

    // Produk & Stok
    Route::get('/products', ProductIndex::class)->name('products.index');
    Route::get('/products/form/{id?}', ProductForm::class)->name('products.form');

    // Produksi Ayam Hidup -> Potongan
    Route::get('/productions', ProductionIndex::class)->name('productions.index');
    Route::get('/productions/form', ProductionForm::class)->name('productions.form');
    Route::get('/productions/{id}', ProductionShow::class)->name('productions.show');

    // Penjualan Kasir POS & Piutang
    Route::get('/sales/pos', Pos::class)->name('sales.pos');
    Route::get('/sales', SaleIndex::class)->name('sales.index');
    Route::get('/sales/{sale}/receipt', SaleReceiptController::class)->name('sales.receipt');

    // Pelanggan
    Route::get('/customers', CustomerIndex::class)->name('customers.index');

    // Tim & Penggajian
    Route::get('/employees', EmployeeIndex::class)->name('employees.index');
    Route::get('/payrolls', PayrollIndex::class)->name('payrolls.index');

    // Buku Kas (Pemasukan & Pengeluaran)
    Route::get('/cash', CashIndex::class)->name('cash.index');

    // Pengaturan
    Route::get('/settings', Settings::class)->name('settings');
});
