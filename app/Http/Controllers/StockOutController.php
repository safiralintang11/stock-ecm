<?php

namespace App\Http\Controllers;

use App\Exports\StockOutsExport;
use App\Exports\Templates\StockOutsTemplate;
use App\Http\Controllers\Concerns\HandlesExcelImport;
use App\Http\Requests\StockOutRequest;
use App\Imports\StockOutsImport;
use App\Models\Product;
use App\Models\StockOut;
use App\Services\Stock\StockEngine;
use App\Services\Stock\StockOutService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class StockOutController extends Controller
{
    use HandlesExcelImport;

    public function __construct(
        protected StockEngine $engine,
        protected StockOutService $outService,
    ) {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $from = $request->input('from_date', '');
        $to = $request->input('to_date', '');

        $outs = StockOut::query()
            ->with(['product:id,sku,name,unit', 'user:id,name'])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('trx_no', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($p) => $p
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"))))
            ->when($from !== '', fn ($q) => $q->whereDate('out_date', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('out_date', '<=', $to))
            ->orderByDesc('out_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $products = Product::active()->orderBy('name')->get(['id', 'name', 'sku', 'unit', 'carton_qty']);

        $today = Carbon::parse($request->input('trx_date', today()->toDateString()));

        $balances = $products->mapWithKeys(fn ($p) => [$p->id => $this->engine->displayBalanceAt($p, $today)]);

        return Inertia::render('StockOuts/Index', [
            'outs' => $outs,
            'products' => $products,
            'balances' => $balances,
            'filters' => ['search' => $search, 'from_date' => $from, 'to_date' => $to],
        ]);
    }

    public function store(StockOutRequest $request)
    {
        $product = Product::findOrFail($request->product_id);

        $out = $this->outService->execute(
            $product,
            (int) $request->qty,
            Carbon::parse($request->trx_date),
            $request->user(),
            $request->note
        );

        return back()->with('success', "OUT {$out->trx_no} berhasil dicatat: {$out->qty} {$product->unit} keluar dari Stock Display.");
    }

    public function export(Request $request)
    {
        return Excel::download(
            new StockOutsExport(
                trim((string) $request->input('search', '')),
                $request->input('from_date', ''),
                $request->input('to_date', '')
            ),
            'stock-out-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(new StockOutsTemplate(), 'template-stock-out.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new StockOutsImport($request->user(), $this->outService);
        $error = $this->importFile($import, $request->file('file'));

        if ($error) {
            return redirect()->back()->with('error', $error);
        }

        return $this->importRedirect($import, "Import Stock OUT selesai: {$import->successCount} baris berhasil diproses.");
    }
}