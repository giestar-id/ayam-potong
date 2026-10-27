<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public bool $lowStock = false;

    public int $perPage = 25;

    // Modal Restock
    public ?int $restockProductId = null;
    public string $restockProductName = '';
    public string $restockUnit = 'kg';
    public float $currentStock = 0;
    public float $currentHpp = 0;
    public string $incomingQty = '';
    public string $incomingCostTotal = '';
    public string $restockNotes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openRestock(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $this->restockProductId = $product->id;
        $this->restockProductName = $product->name;
        $this->restockUnit = $product->unit;
        $this->currentStock = (float) $product->stock;
        $this->currentHpp = (float) $product->average_cost;
        $this->incomingQty = '';
        $this->incomingCostTotal = '';
        $this->restockNotes = '';
        $this->resetErrorBag();
    }

    public function closeRestock(): void
    {
        $this->restockProductId = null;
    }

    public function saveRestock(ProductService $productService): void
    {
        $this->validate([
            'incomingQty' => 'required|numeric|gt:0',
            'incomingCostTotal' => 'required|numeric|gt:0',
            'restockNotes' => 'nullable|string',
        ], [
            'incomingQty.required' => 'Jumlah kuantitas masuk wajib diisi.',
            'incomingQty.gt' => 'Jumlah kuantitas harus lebih besar dari 0.',
            'incomingCostTotal.required' => 'Total biaya pembelian stok wajib diisi.',
            'incomingCostTotal.gt' => 'Total biaya harus lebih besar dari 0.',
        ]);

        $product = Product::findOrFail($this->restockProductId);

        try {
            $productService->restock(
                $product,
                (float) $this->incomingQty,
                (float) $this->incomingCostTotal,
                $this->restockNotes ?: null
            );

            session()->flash('success', "Stok {$product->name} berhasil ditambahkan dan pengeluaran otomatis dicatat.");
            $this->closeRestock();
        } catch (\Exception $e) {
            $this->addError('incomingQty', $e->getMessage());
        }
    }

    public function toggleActive(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->is_active = ! $product->is_active;
        $product->save();
        session()->flash('success', "Status produk {$product->name} diubah menjadi " . ($product->is_active ? 'Aktif' : 'Nonaktif'));
    }

    public function delete(int $productId, ProductService $productService): void
    {
        $product = Product::findOrFail($productId);
        try {
            $productService->deleteProduct($product);
            session()->flash('success', 'Produk berhasil dihapus.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(): View
    {
        $query = Product::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%");
            });
        }

        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($this->lowStock) {
            $query->whereRaw('stock <= min_stock');
        }

        $products = $query->orderBy('name', 'asc')->paginate($this->perPage);

        // Preview Kalkulasi Restock
        $previewNewHpp = null;
        $previewNewStock = null;
        if ((float) $this->incomingQty > 0 && (float) $this->incomingCostTotal > 0) {
            $newStock = $this->currentStock + (float) $this->incomingQty;
            $newHpp = round((($this->currentStock * $this->currentHpp) + (float) $this->incomingCostTotal) / $newStock, 2);
            $previewNewStock = $newStock;
            $previewNewHpp = $newHpp;
        }

        return view('livewire.products.index', [
            'products' => $products,
            'totalProduk' => Product::count(),
            'totalStokKg' => Product::where('unit', 'kg')->sum('stock'),
            'totalStokEkor' => Product::where('unit', 'ekor')->sum('stock'),
            'produkMenipis' => Product::where('is_active', true)->whereRaw('stock <= min_stock')->count(),
            'previewNewHpp' => $previewNewHpp,
            'previewNewStock' => $previewNewStock,
        ]);
    }
}
