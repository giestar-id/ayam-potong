<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    #[Locked]
    public ?int $productId = null;

    public string $sku = '';
    public string $name = '';
    public string $unit = 'kg'; // 'kg' or 'ekor'
    public string $stock = '0';
    public string $min_stock = '5';
    public string $average_cost = '0';
    public string $selling_price = '0';
    public bool $is_active = true;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $product = Product::findOrFail($id);
            $this->productId = $product->id;
            $this->sku = (string) ($product->sku ?? '');
            $this->name = $product->name;
            $this->unit = $product->unit;
            $this->stock = (string) $product->stock;
            $this->min_stock = (string) $product->min_stock;
            $this->average_cost = (string) $product->average_cost;
            $this->selling_price = (string) $product->selling_price;
            $this->is_active = (bool) $product->is_active;
        }
    }

    public function save(ProductService $productService): mixed
    {
        $this->validate([
            'sku' => 'nullable|string|max:50|unique:products,sku,' . ($this->productId ?? 'NULL'),
            'name' => 'required|string|max:255',
            'unit' => 'required|in:kg,ekor',
            'stock' => 'required|numeric|min:0',
            'min_stock' => 'required|numeric|min:0',
            'average_cost' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Nama produk wajib diisi.',
            'selling_price.required' => 'Harga jual wajib diisi.',
        ]);

        $data = [
            'sku' => $this->sku ?: null,
            'name' => $this->name,
            'unit' => $this->unit,
            'stock' => (float) $this->stock,
            'min_stock' => (float) $this->min_stock,
            'average_cost' => (float) $this->average_cost,
            'selling_price' => (float) $this->selling_price,
            'is_active' => $this->is_active,
        ];

        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $productService->updateProduct($product, $data);
            session()->flash('success', "Produk {$product->name} berhasil diperbarui.");
        } else {
            $product = $productService->createProduct($data);
            session()->flash('success', "Produk {$product->name} berhasil ditambahkan.");
        }

        return redirect()->route('products.index');
    }

    public function render(): View
    {
        return view('livewire.products.form');
    }
}
