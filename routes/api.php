<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\CashFlowController;
use App\Http\Controllers\Api\v1\CustomerController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\EmployeeController;
use App\Http\Controllers\Api\v1\PayrollController;
use App\Http\Controllers\Api\v1\PosController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\ProductionController;
use App\Http\Controllers\Api\v1\SaleController;
use App\Http\Controllers\Api\v1\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Ayam Potong Multi-Platform) - PRD Section 5.1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public Auth Endpoints
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/settings/public', [SettingController::class, 'show']);

    // Protected Routes (Laravel Sanctum Token)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth Session Management
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Settings
        Route::get('/settings', [SettingController::class, 'show']);
        Route::put('/settings', [SettingController::class, 'update']);

        // Products & Stock
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::post('/products/{id}/restock', [ProductController::class, 'restock']);
        Route::apiResource('products', ProductController::class)->except(['index', 'store']);

        // Customers
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::apiResource('customers', CustomerController::class)->except(['index']);
        Route::get('/customers/{id}/transactions', [CustomerController::class, 'transactions']);

        // POS & Sales
        Route::post('/pos/checkout', [PosController::class, 'checkout']);
        Route::get('/pos/receipt/{id}', [PosController::class, 'receipt']);
        Route::get('/sales', [SaleController::class, 'index']);
        Route::get('/sales/{id}', [SaleController::class, 'show']);
        Route::post('/sales/{id}/pay-debt', [SaleController::class, 'payDebt']);

        // Dashboard & Cash Flow
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
        Route::get('/cash-flows', [CashFlowController::class, 'index']);
        Route::post('/cash-flows', [CashFlowController::class, 'store']);

        // Productions
        Route::get('/productions', [ProductionController::class, 'index']);
        Route::post('/productions', [ProductionController::class, 'store']);
        Route::post('/productions/{id}/complete', [ProductionController::class, 'complete']);
        Route::put('/productions/{id}/stage', [ProductionController::class, 'updateStage']);
        Route::apiResource('productions', ProductionController::class)->except(['index', 'store']);

        // Employees & Payroll
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::apiResource('employees', EmployeeController::class)->except(['index']);
        Route::get('/payrolls', [PayrollController::class, 'index']);
        Route::post('/payrolls', [PayrollController::class, 'store']);
    });
});
