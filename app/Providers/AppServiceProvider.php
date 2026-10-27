<?php

namespace App\Providers;

use App\Http\Middleware\EnsureOwner;
use App\Models\BusinessSetting;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionOutput;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockPurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::unguard();

        Gate::define('owner', fn (User $user): bool => $user->role === 'owner' || $user->id === (int) User::min('id'));

        Livewire::addPersistentMiddleware([EnsureOwner::class]);

        // Model Observers for automatic cache invalidation (PRD 1.2 D & 5.2)
        $modelsToInvalidate = [
            Product::class,
            StockPurchase::class,
            ProductionBatch::class,
            ProductionOutput::class,
            Sale::class,
            SaleItem::class,
            DebtPayment::class,
            Payroll::class,
            CashFlow::class,
            Customer::class,
            Employee::class,
            BusinessSetting::class,
        ];

        foreach ($modelsToInvalidate as $model) {
            $model::saved(function () {
                Cache::forget('products_catalog_active');
                Cache::forget('business_settings_cache');
                Cache::forget('dashboard_metrics_' . now()->toDateString());
                Cache::forget('dashboard_metrics_default_default');
            });

            $model::deleted(function () {
                Cache::forget('products_catalog_active');
                Cache::forget('business_settings_cache');
                Cache::forget('dashboard_metrics_' . now()->toDateString());
                Cache::forget('dashboard_metrics_default_default');
            });
        }
    }
}
