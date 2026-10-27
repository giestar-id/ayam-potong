<?php

namespace App\Livewire\Sales;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\TransactionService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Pos extends Component
{
    public string $search = '';
    public string $selectedUnit = ''; // filter by 'kg' or 'ekor'

    // Cart items: [productId => ['id' => int, 'name' => string, 'unit' => string, 'price' => float, 'qty' => float, 'subtotal' => float, 'notes' => string]]
    public array $cart = [];

    public ?int $customer_id = null;
    public string $discount_type = 'none'; // 'none', 'percent', 'fixed'
    public float $discount_value = 0;

    public string $payment_method = 'cash'; // 'cash' or 'debt'
    public string $paid_amount = '';
    public string $due_date = '';
    public string $notes = '';

    // Receipt Modal on success
    public ?int $completedSaleId = null;

    public function mount(): void
    {
        $this->due_date = now()->addDays(7)->toDateString();
    }

    public function updatedCustomerId(): void
    {
        if ($this->customer_id) {
            $customer = Customer::find($this->customer_id);
            if ($customer && $customer->discount_type !== 'none' && (float) $customer->discount_value > 0) {
                $this->discount_type = $customer->discount_type;
                $this->discount_value = (float) $customer->discount_value;
            }
        }
    }

    public function addToCart(int $productId): void
    {
        $product = Product::where('is_active', true)->findOrFail($productId);
        $stock = (float) $product->stock;

        if ($stock <= 0) {
            session()->flash('pos_error', "Stok {$product->name} telah habis!");
            return;
        }

        if (isset($this->cart[$productId])) {
            $newQty = $this->cart[$productId]['qty'] + 1;
            if ($newQty > $stock) {
                session()->flash('pos_error', "Stok {$product->name} tidak mencukupi (Tersedia: {$stock} {$product->unit}).");
                return;
            }
            $this->cart[$productId]['qty'] = $newQty;
            $this->cart[$productId]['subtotal'] = round($newQty * $this->cart[$productId]['price'], 2);
        } else {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'unit' => $product->unit,
                'price' => (float) $product->selling_price,
                'qty' => 1.0,
                'subtotal' => (float) $product->selling_price,
                'notes' => '',
            ];
        }

        $this->syncPaidAmount();
    }

    public function updateQty(int $productId, mixed $newQty): void
    {
        $qty = (float) $newQty;
        if ($qty <= 0) {
            $this->removeItem($productId);
            return;
        }

        $product = Product::findOrFail($productId);
        if ($qty > (float) $product->stock) {
            session()->flash('pos_error', "Stok {$product->name} hanya tersisa {$product->stock} {$product->unit}.");
            $qty = (float) $product->stock;
        }

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['qty'] = $qty;
            $this->cart[$productId]['subtotal'] = round($qty * $this->cart[$productId]['price'], 2);
        }

        $this->syncPaidAmount();
    }

    public function removeItem(int $productId): void
    {
        unset($this->cart[$productId]);
        $this->syncPaidAmount();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->paid_amount = '';
        $this->customer_id = null;
        $this->discount_type = 'none';
        $this->discount_value = 0;
        $this->notes = '';
    }

    public function setQuickCash(float|string $amount): void
    {
        if ($amount === 'exact') {
            $this->paid_amount = (string) $this->getGrandTotal();
        } else {
            $this->paid_amount = (string) $amount;
        }
    }

    private function syncPaidAmount(): void
    {
        if ($this->payment_method === 'cash') {
            $this->paid_amount = (string) $this->getGrandTotal();
        }
    }

    public function getSubtotal(): float
    {
        $subtotal = 0.0;
        foreach ($this->cart as $item) {
            $subtotal += (float) ($item['subtotal'] ?? 0);
        }
        return round($subtotal, 2);
    }

    public function getDiscountAmount(): float
    {
        $subtotal = $this->getSubtotal();
        if ($this->discount_type === 'percent' && $this->discount_value > 0) {
            return round($subtotal * ($this->discount_value / 100), 2);
        }
        if ($this->discount_type === 'fixed' && $this->discount_value > 0) {
            return min($subtotal, round($this->discount_value, 2));
        }
        return 0.0;
    }

    public function getGrandTotal(): float
    {
        return max(0.0, round($this->getSubtotal() - $this->getDiscountAmount(), 2));
    }

    public function getChangeAmount(): float
    {
        $grandTotal = $this->getGrandTotal();
        $paid = (float) $this->paid_amount;
        if ($paid > $grandTotal) {
            return round($paid - $grandTotal, 2);
        }
        return 0.0;
    }

    public function getRemainingDebt(): float
    {
        $grandTotal = $this->getGrandTotal();
        $paid = (float) $this->paid_amount;
        if ($paid < $grandTotal) {
            return round($grandTotal - $paid, 2);
        }
        return 0.0;
    }

    public function checkout(TransactionService $transactionService): mixed
    {
        if (empty($this->cart)) {
            session()->flash('pos_error', 'Keranjang belanja masih kosong.');
            return null;
        }

        $grandTotal = $this->getGrandTotal();
        $paid = (float) $this->paid_amount;

        if ($this->payment_method === 'debt' || $paid < $grandTotal) {
            if (!$this->customer_id) {
                session()->flash('pos_error', 'Transaksi piutang / bayar tempo WAJIB memilih pelanggan.');
                return null;
            }
            if (!$this->due_date) {
                session()->flash('pos_error', 'Transaksi piutang WAJIB mengisi tanggal jatuh tempo.');
                return null;
            }
        }

        $items = [];
        foreach ($this->cart as $item) {
            $items[] = [
                'product_id' => $item['id'],
                'quantity' => (float) $item['qty'],
                'unit_price' => (float) $item['price'],
                'notes' => $item['notes'] ?: null,
            ];
        }

        try {
            $sale = $transactionService->createSale([
                'customer_id' => $this->customer_id,
                'items' => $items,
                'discount_type' => $this->discount_type,
                'discount_value' => $this->discount_value,
                'paid_amount' => $paid,
                'due_date' => ($this->payment_method === 'debt' || $paid < $grandTotal) ? $this->due_date : null,
                'notes' => $this->notes ?: null,
            ], auth()->user());

            $this->completedSaleId = $sale->id;
            $this->clearCart();
            session()->flash('pos_success', "Transaksi {$sale->invoice_number} berhasil disimpan!");

            return redirect()->route('sales.receipt', $sale->id);
        } catch (\Exception $e) {
            session()->flash('pos_error', $e->getMessage());
            return null;
        }
    }

    public function render(): View
    {
        $query = Product::where('is_active', true);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%");
            });
        }

        if ($this->selectedUnit !== '') {
            $query->where('unit', $this->selectedUnit);
        }

        $products = $query->orderBy('name', 'asc')->get();
        $customers = Customer::orderBy('name', 'asc')->get();

        return view('livewire.sales.pos', [
            'products' => $products,
            'customers' => $customers,
            'subtotal' => $this->getSubtotal(),
            'discountAmount' => $this->getDiscountAmount(),
            'grandTotal' => $this->getGrandTotal(),
            'changeAmount' => $this->getChangeAmount(),
            'remainingDebt' => $this->getRemainingDebt(),
        ]);
    }
}
