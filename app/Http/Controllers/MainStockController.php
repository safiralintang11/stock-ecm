<?php

namespace App\Http\Controllers;

use App\Exports\MainStocksExport;
use App\Exports\Templates\MainStocksTemplate;
use App\Http\Controllers\Concerns\HandlesExcelImport;
use App\Http\Requests\StockInRequest;
use App\Imports\MainStocksImport;
use App\Models\Product;
use App\Models\StockHistory;
use App\Services\Stock\StockEngine;
use App\Services\Stock\StockInService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class MainStockController extends Controller
{
    use HandlesExcelImport;

    public function __construct(
        protected StockEngine $engine,
        protected StockInService $stockInService,
    ) {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $products = Product::query()
            ->withBalances()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'unit' => $p->unit,
                'carton_qty' => $p->carton_qty,
                'main_qty' => (int) ($p->main_qty ?? 0),
                'display_qty' => (int) ($p->display_qty ?? 0),
            ]);

        $histories = StockHistory::query()
            ->whereIn('type', ['INITIAL_STOCK', 'STOCK_IN'])
            ->with(['product:id,sku,name,unit', 'user:id,name'])
            ->when($search !== '', fn ($q) => $q->whereHas('product', fn ($p) => $p
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->orderByDesc('trx_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MainStocks/Index', [
            'products' => $products,
            'histories' => $histories,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(StockInRequest $request)
    {
        $product = Product::findOrFail($request->product_id);

        $this->stockInService->execute(
            $product,
            (int) $request->qty,
            \Illuminate\Support\Carbon::parse($request->trx_date),
            $request->user(),
            $request->note
        );

        return back()->with('success', "Stock Utama {$product->name} berhasil ditambahkan.");
    }

    public function export(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        return Excel::download(
            new MainStocksExport($search),
            'stock-utama-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(new MainStocksTemplate(), 'template-stock-utama.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new MainStocksImport($request->user(), $this->stockInService);
        $error = $this->importFile($import, $request->file('file'));

        if ($error) {
            return redirect()->back()->with('error', $error);
        }

        return $this->importRedirect($import, "Import Stock Utama selesai: {$import->successCount} baris berhasil diproses.");
    }
}