<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTransactionAndDebtTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $productAyam;
    protected Customer $customerRestaurant;
    protected TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();

        BusinessSetting::create([
            'business_name' => 'Ayam Potong Berkah',
            'phone' => '08123456789',
            'address' => 'Jl. Pasar Unggas No. 10',
            'receipt_footer' => 'Terima Kasih Atas Kunjungan Anda',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Kasir 1',
            'email' => 'kasir@ayampotong.com',
            'role' => 'cashier',
        ]);

        $this->productAyam = Product::create([
            'sku' => 'AYM-FILLET',
            'name' => 'Dada Fillet Ayam',
            'unit' => 'kg',
            'selling_price' => 45000,
            'average_cost' => 32000,
            'stock' => 50.0, // 50 kg available
            'min_stock' => 5.0,
            'is_active' => true,
        ]);

        $this->customerRestaurant = Customer::create([
            'name' => 'Warung Nasi Bu Siti',
            'phone' => '087788991122',
            'discount_type' => 'percent',
            'discount_value' => 10.0, // 10% discount
        ]);

        $this->transactionService = app(TransactionService::class);
    }

    public function test_pos_sale_calculates_discount_reduces_stock_and_records_cash_income(): void
    {
        // Customer buys 4.5 kg of Fillet Ayam (4.5 * 45,000 = 202,500)
        // With 10% customer discount = 20,250 discount -> grand total = 182,250
        // Customer pays 200,000 in cash -> change = 17,750
        $items = [
            [
                'product_id' => $this->productAyam->id,
                'quantity' => 4.5,
                'unit_price' => 45000,
            ]
        ];

        $sale = $this->transactionService->createSale([
            'customer_id' => $this->customerRestaurant->id,
            'paid_amount' => 200000,
            'items' => $items,
        ], $this->user);

        $this->assertEquals(202500, (float) $sale->subtotal);
        $this->assertEquals(20250, (float) $sale->discount_amount);
        $this->assertEquals(182250, (float) $sale->grand_total);
        $this->assertEquals(200000, (float) $sale->paid_amount);
        $this->assertEquals(0, (float) $sale->remaining_debt);
        $this->assertEquals('paid', $sale->payment_status);

        // Verify stock reduced by 4.5 kg: 50.0 - 4.5 = 45.5 kg
        $this->productAyam->refresh();
        $this->assertEquals(45.5, (float) $this->productAyam->stock);

        // Verify cash flow recorded income of 182,250
        $this->assertDatabaseHas('cash_flows', [
            'type' => 'income',
            'category' => 'sales',
            'amount' => 182250,
        ]);
    }

    public function test_pos_tempo_underpayment_creates_customer_debt_and_payment_records_installment(): void
    {
        // Sale of 10 kg fillet (450,000). Customer pays DP 100,000, remaining 350,000 as tempo/debt
        $items = [
            [
                'product_id' => $this->productAyam->id,
                'quantity' => 10.0,
                'unit_price' => 45000,
            ]
        ];

        $sale = $this->transactionService->createSale([
            'customer_id' => $this->customerRestaurant->id,
            'discount_type' => 'none',
            'discount_value' => 0,
            'paid_amount' => 100000,
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => $items,
        ], $this->user);

        $this->assertEquals('partial', $sale->payment_status);
        $this->assertEquals(305000, (float) $sale->remaining_debt);

        // DP of 100,000 recorded in cash flow
        $this->assertDatabaseHas('cash_flows', [
            'type' => 'income',
            'category' => 'sales',
            'amount' => 100000,
        ]);

        // Now pay installment of 155,000
        $debtPayment = $this->transactionService->payDebt(
            $sale,
            155000,
            'Cicilan pertama warung Bu Siti'
        );

        $sale->refresh();
        $this->assertEquals(150000, (float) $sale->remaining_debt);
        $this->assertEquals('partial', $sale->payment_status);

        // Pay remaining 150,000
        $this->transactionService->payDebt(
            $sale,
            150000,
            'Pelunasan sisa piutang'
        );

        $sale->refresh();
        $this->assertEquals(0, (float) $sale->remaining_debt);
        $this->assertEquals('paid', $sale->payment_status);

        // Cash flow should have entries for debt installments
        $this->assertDatabaseHas('cash_flows', [
            'type' => 'income',
            'category' => 'debt_payment',
            'amount' => 155000,
        ]);
        $this->assertDatabaseHas('cash_flows', [
            'type' => 'income',
            'category' => 'debt_payment',
            'amount' => 150000,
        ]);
    }
}
