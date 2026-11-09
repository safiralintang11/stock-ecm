<?php

namespace App\Http\Controllers;

use App\Exports\StockTransfersExport;
use App\Exports\Templates\StockTransfersTemplate;
use App\Http\Controllers\Concerns\HandlesExcelImport;
use App\Http\Requests\TransferRequest;
use App\Imports\StockTransfersImport;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\Stock\StockEngine;
use App\Services\Stock\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class StockTransferController extends Controller
{
    use HandlesExcelImport;

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

    public function export(Request $request)
    {
        return Excel::download(
            new StockTransfersExport(
                trim((string) $request->input('search', '')),
                $request->input('from_date', ''),
                $request->input('to_date', '')
            ),
            'stock-transfer-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(new StockTransfersTemplate(), 'template-stock-transfer.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new StockTransfersImport($request->user(), $this->transferService);
        $error = $this->importFile($import, $request->file('file'));

        if ($error) {
            return redirect()->back()->with('error', $error);
        }

        return $this->importRedirect($import, "Import Stock Transfer selesai: {$import->successCount} baris berhasil diproses.");
    }
}