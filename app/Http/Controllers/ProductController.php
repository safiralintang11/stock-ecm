<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Exports\Templates\ProductsTemplate;
use App\Http\Controllers\Concerns\HandlesExcelImport;
use App\Http\Requests\ProductRequest;
use App\Imports\ProductsImport;
use App\Models\Product;
use App\Services\Stock\StockEngine;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    use HandlesExcelImport;

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

    public function export(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', '');

        return Excel::download(
            new ProductsExport($search, $status),
            'master-produk-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(new ProductsTemplate(), 'template-produk.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new ProductsImport($request->user());
        $error = $this->importFile($import, $request->file('file'));

        if ($error) {
            return redirect()->back()->with('error', $error);
        }

        return $this->importRedirect(
            $import,
            "Import produk selesai: {$import->created} produk baru, {$import->updated} produk diperbarui."
        );
    }
}