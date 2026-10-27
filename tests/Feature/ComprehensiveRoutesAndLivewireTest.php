<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveRoutesAndLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Product $sampleProduct;
    protected Customer $sampleCustomer;
    protected Sale $sampleSale;
    protected ProductionBatch $sampleBatch;

    protected function setUp(): void
    {
        parent::setUp();

        BusinessSetting::current();

        $this->owner = User::factory()->create([
            'name' => 'Owner Test',
            'email' => 'owner@ayampotong.com',
            'role' => 'owner',
        ]);

        $this->sampleProduct = Product::create([
            'sku' => 'TEST-01',
            'name' => 'Ayam Karkas Test',
            'unit' => 'kg',
            'stock' => 100,
            'min_stock' => 10,
            'selling_price' => 38000,
            'average_cost' => 28000,
            'is_active' => true,
        ]);

        $this->sampleCustomer = Customer::create([
            'name' => 'Warung Test',
            'phone' => '0812345678',
            'discount_type' => 'percent',
            'discount_value' => 5,
        ]);

        $this->sampleBatch = ProductionBatch::create([
            'batch_number' => 'BATCH-TEST-001',
            'production_date' => now()->toDateString(),
            'live_bird_count' => 50,
            'live_weight_kg' => 100.0,
            'raw_material_cost' => 2200000,
            'additional_cost' => 150000,
            'total_output_weight_kg' => 75.0,
            'shrinkage_kg' => 25.0,
            'status' => 'completed',
        ]);

        $this->sampleSale = Sale::create([
            'invoice_number' => 'INV-TEST-001',
            'customer_id' => $this->sampleCustomer->id,
            'user_id' => $this->owner->id,
            'transaction_date' => now()->toDateString(),
            'subtotal' => 76000,
            'discount_type' => 'percent',
            'discount_amount' => 3800,
            'grand_total' => 72200,
            'paid_amount' => 72200,
            'remaining_debt' => 0,
            'payment_status' => 'paid',
        ]);

        SaleItem::create([
            'sale_id' => $this->sampleSale->id,
            'product_id' => $this->sampleProduct->id,
            'quantity' => 2,
            'unit' => 'kg',
            'unit_price' => 38000,
            'cost_price_at_sale' => 28000,
            'subtotal' => 76000,
        ]);
    }

    public function test_all_web_routes_render_cleanly_for_authenticated_owner(): void
    {
        $routes = [
            '/',
            '/products',
            '/products/form',
            '/products/form/' . $this->sampleProduct->id,
            '/productions',
            '/productions/form',
            '/productions/' . $this->sampleBatch->id,
            '/sales',
            '/sales/pos',
            '/sales/' . $this->sampleSale->id . '/receipt',
            '/customers',
            '/employees',
            '/payrolls',
            '/cash',
            '/settings',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->owner)->get($route);
            $response->assertStatus(200);
        }
    }

    public function test_guest_is_redirected_to_login_on_protected_routes(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');

        $loginResponse = $this->get('/login');
        $loginResponse->assertStatus(200);
    }

    public function test_all_core_api_endpoints_return_valid_json(): void
    {
        // 1. Auth login
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@ayampotong.com',
            'password' => 'password',
        ]);
        $loginRes->assertStatus(200)->assertJsonStructure([
            'success',
            'data' => ['token', 'user'],
        ]);
        $token = $loginRes->json('data.token');

        $headers = ['Authorization' => 'Bearer ' . $token];

        // 2. Dashboard summary
        $this->getJson('/api/v1/dashboard/summary', $headers)
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);

        // 3. Products
        $this->getJson('/api/v1/products', $headers)
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);

        // 4. Customers
        $this->getJson('/api/v1/customers', $headers)
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);

        // 5. Sales
        $this->getJson('/api/v1/sales', $headers)
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);

        // 6. POS Checkout API
        $checkoutRes = $this->postJson('/api/v1/pos/checkout', [
            'customer_id' => $this->sampleCustomer->id,
            'paid_amount' => 50000,
            'items' => [
                [
                    'product_id' => $this->sampleProduct->id,
                    'quantity' => 1.0,
                    'unit_price' => 38000,
                ]
            ],
        ], $headers);
        $checkoutRes->assertStatus(201)->assertJsonStructure(['success', 'data']);

        // 7. Employees
        $this->getJson('/api/v1/employees', $headers)
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);

        // 8. Payrolls
        $this->getJson('/api/v1/payrolls', $headers)
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);

        // 9. Cash flows
        $this->getJson('/api/v1/cash-flows', $headers)
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);

        // 10. Settings
        $this->getJson('/api/v1/settings', $headers)
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);

        // 11. Productions
        $this->getJson('/api/v1/productions', $headers)
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }
}
