<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\DebtPayment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    public function generateInvoiceNumber(): string
    {
        $dateStr = now()->format('Ymd');
        $countToday = Sale::whereDate('created_at', now()->toDateString())->count() + 1;

        return 'INV-' . $dateStr . '-' . str_pad((string) $countToday, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Checkout Transaksi Kasir POS (PRD 3.4 & 1.2 ACID / Locking)
     */
    public function createSale(array $data, ?User $user = null): Sale
    {
        if (empty($data['items'])) {
            throw new InvalidArgumentException('Keranjang belanja tidak boleh kosong.');
        }

        return DB::transaction(function () use ($data, $user) {
            $invoiceNumber = $data['invoice_number'] ?? $this->generateInvoiceNumber();
            $transactionDate = $data['transaction_date'] ?? now()->toDateString();
            $customerId = $data['customer_id'] ?? null;
            $customer = $customerId ? Customer::find($customerId) : null;

            // 1. Validasi stok dengan Pessimistic Locking
            $lineItems = [];
            $subtotal = 0.0;

            foreach ($data['items'] as $item) {
                $productId = $item['product_id'];
                $requestedQty = (float) $item['quantity'];

                if ($requestedQty <= 0) {
                    throw new InvalidArgumentException('Kuantitas produk harus lebih besar dari 0.');
                }

                $product = Product::lockForUpdate()->findOrFail($productId);

                $unitPrice = isset($item['unit_price']) && (float) $item['unit_price'] >= 0
                    ? (float) $item['unit_price']
                    : (float) $product->selling_price;

                $lineSubtotal = round($requestedQty * $unitPrice, 2);
                $subtotal += $lineSubtotal;

                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $requestedQty,
                    'unit' => $item['unit'] ?? $product->unit,
                    'unit_price' => $unitPrice,
                    'cost_price_at_sale' => (float) $product->average_cost,
                    'subtotal' => $lineSubtotal,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            // 2. Kalkulasi Diskon Otomatis Pelanggan atau Diskon Manual (PRD 3.4)
            $discountType = $data['discount_type'] ?? null;
            $discountValue = (float) ($data['discount_value'] ?? 0);

            // Jika tidak ada diskon manual dan ada pelanggan dengan diskon default
            if ((!$discountType || $discountType === 'none') && $customer && $customer->discount_type !== 'none') {
                $discountType = $customer->discount_type;
                $discountValue = (float) $customer->discount_value;
            }

            $discountAmount = 0.0;
            if ($discountType === 'percent' && $discountValue > 0) {
                $discountAmount = round($subtotal * ($discountValue / 100), 2);
            } elseif ($discountType === 'fixed' && $discountValue > 0) {
                $discountAmount = min($subtotal, round($discountValue, 2));
            }

            $grandTotal = max(0.0, round($subtotal - $discountAmount, 2));
            $paidAmount = (float) ($data['paid_amount'] ?? 0);

            // 3. Evaluasi Status Pembayaran (Lunas vs Piutang)
            $dueDate = $data['due_date'] ?? null;
            if ($paidAmount >= $grandTotal) {
                $paymentStatus = 'paid';
                $remainingDebt = 0.0;
            } else {
                // Kurang bayar = Piutang (Wajib pilih pelanggan & jatuh tempo)
                if (!$customerId) {
                    throw new InvalidArgumentException('Transaksi piutang wajib memilih pelanggan.');
                }
                if (!$dueDate) {
                    throw new InvalidArgumentException('Transaksi piutang wajib mengisi tanggal jatuh tempo.');
                }

                $remainingDebt = round($grandTotal - $paidAmount, 2);
                $paymentStatus = $paidAmount > 0 ? 'partial' : 'unpaid';
            }

            // 4. Buat Record Penjualan
            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customerId,
                'user_id' => $user?->id ?? auth()->id(),
                'transaction_date' => $transactionDate,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_amount' => $discountAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'remaining_debt' => $remainingDebt,
                'payment_status' => $paymentStatus,
                'due_date' => $dueDate,
                'notes' => $data['notes'] ?? null,
            ]);

            // 5. Potong Stok & Buat Sale Items
            foreach ($lineItems as $item) {
                $product = $item['product'];
                $product->stock = (float) $product->stock - $item['quantity'];
                $product->save();

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'unit' => $item['unit'],
                    'quantity' => $item['quantity'],
                    'cost_price_at_sale' => $item['cost_price_at_sale'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'notes' => $item['notes'],
                ]);
            }

            // 6. Catat Kas Masuk (Pemasukan)
            $actualReceived = min($paidAmount, $grandTotal);
            if ($actualReceived > 0) {
                CashFlow::create([
                    'type' => 'income',
                    'category' => 'sales',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'transaction_date' => $transactionDate,
                    'amount' => $actualReceived,
                    'description' => ($paymentStatus === 'paid' ? 'Penjualan tunai lunas nota ' : 'DP penjualan piutang nota ') . $sale->invoice_number,
                ]);
            }

            $this->invalidateCache();

            return $sale->load(['items.product', 'customer', 'user']);
        });
    }

    /**
     * Catat Pembayaran / Pelunasan Piutang (PRD 3.5)
     */
    public function payDebt(
        Sale $sale,
        float $amount,
        ?string $notes = null,
        ?string $paymentDate = null
    ): DebtPayment {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Nominal pembayaran piutang harus lebih besar dari 0.');
        }

        if ($sale->payment_status === 'paid' || (float) $sale->remaining_debt <= 0) {
            throw new InvalidArgumentException('Nota penjualan ini sudah berstatus lunas.');
        }

        if ($amount > (float) $sale->remaining_debt) {
            throw new InvalidArgumentException('Nominal pembayaran melebihi sisa piutang (Sisa: Rp ' . number_format($sale->remaining_debt, 0, ',', '.') . ').');
        }

        return DB::transaction(function () use ($sale, $amount, $notes, $paymentDate) {
            $sale = Sale::lockForUpdate()->findOrFail($sale->id);

            $date = $paymentDate ? date('Y-m-d', strtotime($paymentDate)) : now()->toDateString();

            $payment = DebtPayment::create([
                'sale_id' => $sale->id,
                'payment_date' => $date,
                'amount' => $amount,
                'notes' => $notes,
            ]);

            $newRemaining = max(0.0, round((float) $sale->remaining_debt - $amount, 2));
            $sale->remaining_debt = $newRemaining;
            $sale->paid_amount = (float) $sale->paid_amount + $amount;

            if ($newRemaining <= 0) {
                $sale->payment_status = 'paid';
            } else {
                $sale->payment_status = 'partial';
            }
            $sale->save();

            // PRD 3.5: Nominal pembayaran otomatis tercatat di menu Pemasukan
            CashFlow::create([
                'type' => 'income',
                'category' => 'debt_payment',
                'reference_type' => DebtPayment::class,
                'reference_id' => $payment->id,
                'transaction_date' => $date,
                'amount' => $amount,
                'description' => "Pelunasan/Cicilan piutang nota {$sale->invoice_number}" . ($sale->customer ? " ({$sale->customer->name})" : ''),
            ]);

            $this->invalidateCache();

            return $payment;
        });
    }

    public function invalidateCache(): void
    {
        Cache::flush();
    }
}
