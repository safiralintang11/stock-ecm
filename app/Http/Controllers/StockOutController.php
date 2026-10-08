<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockOutRequest;
use App\Models\Product;
use App\Models\StockOut;
use App\Services\Stock\StockEngine;
use App\Services\Stock\StockOutService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class StockOutController extends Controller
{
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
}
