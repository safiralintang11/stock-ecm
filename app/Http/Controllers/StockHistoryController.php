<?php

namespace App\Http\Controllers;

use App\Exports\StockHistoryExport;
use App\Models\StockHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class StockHistoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $type = $request->input('type', '');
        $from = $request->input('from_date', '');
        $to = $request->input('to_date', '');

        $histories = StockHistory::query()
            ->with(['product:id,sku,name,unit', 'user:id,name'])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($p) => $p
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"))))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($from !== '', fn ($q) => $q->whereDate('trx_date', '>=', $from))
            ->when($to !== '', fn ($q) => $q->whereDate('trx_date', '<=', $to))
            ->orderByDesc('trx_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Reports/StockHistory', [
            'histories' => $histories,
            'filters' => ['search' => $search, 'type' => $type, 'from_date' => $from, 'to_date' => $to],
            'types' => StockHistory::TYPES,
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(
            new StockHistoryExport(
                trim((string) $request->input('search', '')),
                $request->input('type', ''),
                $request->input('from_date', ''),
                $request->input('to_date', '')
            ),
            'stock-history-'.now()->format('Ymd-His').'.xlsx'
        );
    }
}