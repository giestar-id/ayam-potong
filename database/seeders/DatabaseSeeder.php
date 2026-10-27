<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\User;
use App\Services\PayrollService;
use App\Services\ProductionService;
use App\Services\ProductService;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. User Owner
        $user = User::firstOrCreate(
            ['email' => 'admin@ayampotong.com'],
            [
                'name' => 'Pak Anas',
                'password' => Hash::make('password'),
                'role' => 'owner',
            ]
        );

        // 1b. User Kasir Mobile
        User::firstOrCreate(
            ['email' => 'kasir@ayampotong.com'],
            [
                'name' => 'Naufal (Kasir)',
                'password' => Hash::make('password'),
                'role' => 'cashier',
            ]
        );

        // 2. Business Settings
        BusinessSetting::updateOrCreate(
            ['id' => 1],
            [
                'business_name' => 'Ayam Potong Segar Berkah',
                'address' => 'Jl. Pasar Unggas No. 12, Jawa Timur',
                'phone' => '0812-3456-7890',
                'receipt_footer' => 'Terima kasih atas kepercayaan Anda. Ayam segar, higienis, dan bersertifikat halal!',
            ]
        );

        // 3. Produk Ayam
        $pUtuh = Product::create([
            'sku' => 'AYM-UTUH-01',
            'name' => 'Ayam Broiler Utuh',
            'unit' => 'kg',
            'stock' => 45.5,
            'min_stock' => 10,
            'average_cost' => 26000,
            'selling_price' => 35000,
            'is_active' => true,
        ]);

        $pPejantan = Product::create([
            'sku' => 'AYM-PEJ-02',
            'name' => 'Ayam Pejantan Utuh',
            'unit' => 'ekor',
            'stock' => 30,
            'min_stock' => 8,
            'average_cost' => 32000,
            'selling_price' => 42000,
            'is_active' => true,
        ]);

        $pDada = Product::create([
            'sku' => 'PRT-DADA-03',
            'name' => 'Dada Ayam Fillet',
            'unit' => 'kg',
            'stock' => 28.0,
            'min_stock' => 8,
            'average_cost' => 38000,
            'selling_price' => 48000,
            'is_active' => true,
        ]);

        $pPaha = Product::create([
            'sku' => 'PRT-PAHA-04',
            'name' => 'Paha Bawah (Drumstick)',
            'unit' => 'kg',
            'stock' => 22.5,
            'min_stock' => 6,
            'average_cost' => 32000,
            'selling_price' => 40000,
            'is_active' => true,
        ]);

        $pSayap = Product::create([
            'sku' => 'PRT-SYP-05',
            'name' => 'Sayap Ayam Segar',
            'unit' => 'kg',
            'stock' => 16.0,
            'min_stock' => 5,
            'average_cost' => 25000,
            'selling_price' => 34000,
            'is_active' => true,
        ]);

        $pHati = Product::create([
            'sku' => 'JRN-HATI-06',
            'name' => 'Hati & Ampela Ayam',
            'unit' => 'ekor',
            'stock' => 40,
            'min_stock' => 10,
            'average_cost' => 2500,
            'selling_price' => 4000,
            'is_active' => true,
        ]);

        $pCeker = Product::create([
            'sku' => 'JRN-CKR-07',
            'name' => 'Ceker Ayam Bersih',
            'unit' => 'kg',
            'stock' => 3.5, // low stock
            'min_stock' => 5,
            'average_cost' => 16000,
            'selling_price' => 24000,
            'is_active' => true,
        ]);

        $pTulang = Product::create([
            'sku' => 'SMP-TLG-08',
            'name' => 'Kerongkong / Tulang Sup',
            'unit' => 'kg',
            'stock' => 15.0,
            'min_stock' => 5,
            'average_cost' => 10000,
            'selling_price' => 16000,
            'is_active' => true,
        ]);

        // 4. Pelanggan
        $cWarung = Customer::create([
            'name' => 'Warung Makan Barokah',
            'phone' => '081298765432',
            'address' => 'Jl. Raya Timur No. 44',
            'notes' => 'Langganan dada fillet & paha tiap 2 hari',
            'discount_type' => 'percent',
            'discount_value' => 5, // 5%
        ]);

        $cKatering = Customer::create([
            'name' => 'Katering Ibu Siti',
            'phone' => '081322334455',
            'address' => 'Komplek Griya Indah Blok B-3',
            'notes' => 'Pesanan partai besar mingguan',
            'discount_type' => 'fixed',
            'discount_value' => 2000, // Rp 2.000
        ]);

        $cResto = Customer::create([
            'name' => 'RM Padang Selera Minang',
            'phone' => '085711223344',
            'address' => 'Jl. Pemuda No. 8',
            'notes' => 'Pembayaran tempo jatuh tempo 7 hari',
            'discount_type' => 'none',
            'discount_value' => 0,
        ]);

        // 5. Karyawan
        $emp1 = Employee::create([
            'name' => 'Joko Widodo',
            'phone' => '081211112222',
            'position' => 'Kepala Potong Ayam',
            'daily_rate' => 110000,
            'is_active' => true,
        ]);

        $emp2 = Employee::create([
            'name' => 'Supriyanto',
            'phone' => '081233334444',
            'position' => 'Tim Parting & Pembersihan',
            'daily_rate' => 100000,
            'is_active' => true,
        ]);

        $emp3 = Employee::create([
            'name' => 'Agus Setiawan',
            'phone' => '081255556666',
            'position' => 'Timbang & Packing',
            'daily_rate' => 95000,
            'is_active' => true,
        ]);

        $emp4 = Employee::create([
            'name' => 'Slamet Riyadi',
            'phone' => '081277778888',
            'position' => 'Driver & Pengiriman',
            'daily_rate' => 100000,
            'is_active' => true,
        ]);

        $emp5 = Employee::create([
            'name' => 'Rina Marlina',
            'phone' => '081299990000',
            'position' => 'Kasir & Admin',
            'daily_rate' => 90000,
            'is_active' => true,
        ]);

        // 6. Jalankan Transaksi Contoh via Service Layer
        $txService = app(TransactionService::class);
        $prodService = app(ProductionService::class);
        $payrollService = app(PayrollService::class);

        // Batch Produksi Aktif (sedang berjalan)
        $batchAktif = $prodService->createBatch([
            'live_weight_kg' => 300,
            'live_bird_count' => 150,
            'raw_material_cost' => 6600000,
            'additional_cost' => 150000,
            'production_date' => now()->toDateString(),
            'notes' => 'Proses pemotongan ayam hidup kiriman pagi',
        ]);

        // Transaksi Lunas 1 (Warung Barokah dengan diskon 5%)
        $txService->createSale([
            'customer_id' => $cWarung->id,
            'transaction_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $pDada->id, 'quantity' => 5.0],
                ['product_id' => $pPaha->id, 'quantity' => 4.0],
            ],
            'paid_amount' => 500000,
            'notes' => 'Pesanan rutin pagi',
        ], $user);

        // Transaksi Piutang (RM Padang Selera Minang - jatuh tempo H+3)
        $txService->createSale([
            'customer_id' => $cResto->id,
            'transaction_date' => now()->subDay()->toDateString(),
            'items' => [
                ['product_id' => $pUtuh->id, 'quantity' => 10.0],
                ['product_id' => $pHati->id, 'quantity' => 10],
            ],
            'paid_amount' => 150000, // DP 150.000, sisa piutang
            'due_date' => now()->addDays(2)->toDateString(),
            'notes' => 'Nota tempo untuk stok resto',
        ], $user);

        // Payroll contoh
        $payrollService->createPayroll(
            $emp1,
            6.0,
            now()->subDays(7)->toDateString(),
            now()->subDay()->toDateString(),
            'Gajian mingguan periode 1'
        );

        // Pengeluaran operasional manual (listrik, es batu, plastik)
        CashFlow::create([
            'type' => 'expense',
            'category' => 'operational_utility',
            'transaction_date' => now()->toDateString(),
            'amount' => 120000,
            'description' => 'Pembelian es batu balok (4 balok) untuk rendaman karkas',
        ]);

        CashFlow::create([
            'type' => 'expense',
            'category' => 'operational_utility',
            'transaction_date' => now()->toDateString(),
            'amount' => 85000,
            'description' => 'Kantong plastik bening tebal 1kg & 2kg',
        ]);
    }
}
