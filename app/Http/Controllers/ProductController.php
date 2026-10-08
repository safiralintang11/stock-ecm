<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\Stock\StockEngine;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __construct(protected StockEngine $engine)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', '');

        $products = Product::query()
            ->withBalances()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => ['search' => $search, 'status' => $status],
            'canManage' => $request->user()->isAdmin(),
        ]);
    }

    public function store(ProductRequest $request)
    {
        $product = Product::create($request->validated());

        $this->engine->writeAudit('Create Product', 'Master Produk', $product->sku, null, $product->toArray());

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(ProductRequest $request, Product $product)
    {
        $before = $product->toArray();
        $product->update($request->validated());

        $this->engine->writeAudit('Update Product', 'Master Produk', $product->sku, $before, $product->fresh()->toArray());

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->histories()->exists()) {
            return back()->with('error', 'Produk sudah digunakan dalam transaksi dan tidak dapat dihapus. Nonaktifkan produk saja.');
        }

        $sku = $product->sku;
        $product->delete();

        $this->engine->writeAudit('Delete Product', 'Master Produk', $sku, null, null);

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
