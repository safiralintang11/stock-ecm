<?php

namespace App\Http\Controllers;

use App\Exports\DisplayStocksExport;
use App\Exports\Templates\DisplayStocksTemplate;
use App\Http\Controllers\Concerns\HandlesExcelImport;
use App\Imports\DisplayStocksImport;
use App\Models\DisplayStock;
use App\Models\Product;
use App\Services\Stock\StockEngine;
use App\Services\Stock\StockTransferService;
use App\Http\Requests\TransferRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class DisplayStockController extends Controller
{
    use HandlesExcelImport;

    public function __construct(
        protected StockEngine $engine,
        protected StockTransferService $transferService,
    ) {
    }

    public function index(Request $request)
    {
        $date = Carbon::parse($request->input('date', today()->toDateString()));
        $search = trim((string) $request->input('search', ''));

        $products = Product::query()
            ->withBalances()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->orderBy('name')
            ->get();

        $dayRows = DisplayStock::whereDate('stock_date', $date->toDateString())
            ->get()
            ->keyBy('product_id');

        $prevClosings = DisplayStock::whereDate('stock_date', '<', $date->toDateString())
            ->orderBy('stock_date')
            ->get()
            ->groupBy('product_id')
            ->map(fn ($g) => (int) $g->last()->closing_qty);

        $rows = $products->map(function (Product $p) use ($date, $dayRows, $prevClosings) {
            $day = $dayRows[$p->id] ?? null;
            $mainQty = (int) ($p->main_qty ?? 0);
            $displayQty = $day ? (int) $day->closing_qty : (int) ($prevClosings[$p->id] ?? 0);

            return [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'unit' => $p->unit,
                'carton_qty' => $p->carton_qty,
                'min_stock' => $p->min_stock,
                'main_qty' => $mainQty,
                'total_received' => (int) ($p->total_received ?? 0),
                'display_qty' => $displayQty,
                'opening_qty' => $day ? (int) $day->opening_qty : null,
                'in_qty' => $day ? (int) $day->in_qty : 0,
                'out_qty' => $day ? (int) $day->out_qty : 0,
                'stock_status' => $displayQty <= 0 ? 'OUT_OF_STOCK' : ($displayQty <= $p->min_stock ? 'LOW_STOCK' : 'OK'),
            ];
        });

        return Inertia::render('DisplayStocks/Index', [
            'date' => $date->toDateString(),
            'search' => $search,
            'rows' => $rows,
        ]);
    }

    /**
     * Add display stock = Stock Transfer from main to display.
     */
    public function store(TransferRequest $request)
    {
        $product = Product::findOrFail($request->product_id);

        $this->transferService->execute(
            $product,
            (int) $request->qty,
            Carbon::parse($request->trx_date),
            $request->user(),
            $request->note
        );

        return back()->with('success', "Stock Display {$product->name} berhasil ditambahkan (Stock Transfer dicatat).");
    }

    public function export(Request $request)
    {
        $date = $request->input('date', today()->toDateString());
        $search = trim((string) $request->input('search', ''));

        return Excel::download(
            new DisplayStocksExport($date, $search),
            'stock-display-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(new DisplayStocksTemplate(), 'template-stock-display.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new DisplayStocksImport($request->user(), $this->transferService);
        $error = $this->importFile($import, $request->file('file'));

        if ($error) {
            return redirect()->back()->with('error', $error);
        }

        return $this->importRedirect($import, "Import Stock Display selesai: {$import->successCount} baris berhasil ditambahkan (Stock Transfer).");
    }
}