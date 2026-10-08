<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\Stock\StockEngine;
use App\Services\Stock\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class StockTransferController extends Controller
{
    public function __construct(
        protected StockEngine $engine,
        protected StockTransferService $transferService,
    ) {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $from = $request->input('from_date', '');
        $to = $request->input('to_date', '');

        $transfers = StockTransfer::query()
            ->with(['product:id,sku,name,unit', 'user:id,name'])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('trx_no', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($p) => $p
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"))))
            ->when($from !== '', fn ($q) => $q->whereDate('transfer_date', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('transfer_date', '<=', $to))
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $products = Product::active()->orderBy('name')->get(['id', 'name', 'sku', 'unit', 'carton_qty']);

        $balances = $products->mapWithKeys(fn ($p) => [$p->id => (int) ($p->mainStock()->first()->qty ?? 0)]);

        return Inertia::render('StockTransfers/Index', [
            'transfers' => $transfers,
            'products' => $products,
            'balances' => $balances,
            'filters' => ['search' => $search, 'from_date' => $from, 'to_date' => $to],
        ]);
    }

    public function store(TransferRequest $request)
    {
        $product = Product::findOrFail($request->product_id);

        $transfer = $this->transferService->execute(
            $product,
            (int) $request->qty,
            Carbon::parse($request->trx_date),
            $request->user(),
            $request->note
        );

        return back()->with('success', "Transfer {$transfer->trx_no} berhasil: {$transfer->qty} {$product->unit} dari Stock Utama ke Stock Display.");
    }
}
