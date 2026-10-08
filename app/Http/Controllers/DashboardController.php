<?php

namespace App\Http\Controllers;

use App\Models\DisplayStock;
use App\Models\MainStock;
use App\Models\Product;
use App\Models\StockHistory;
use App\Models\StockOut;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
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

        $outByProduct = StockOut::selectRaw('product_id, COALESCE(SUM(qty),0) as total')
            ->whereDate('out_date', $date->toDateString())
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $dayRows = DisplayStock::whereDate('stock_date', $date->toDateString())
            ->get()
            ->keyBy('product_id');

        $prevClosings = DisplayStock::whereDate('stock_date', '<', $date->toDateString())
            ->orderBy('stock_date')
            ->get()
            ->groupBy('product_id')
            ->map(fn ($g) => (int) $g->last()->closing_qty);

        $rows = $products->map(function (Product $p) use ($outByProduct, $dayRows, $prevClosings, $date) {
            $day = $dayRows[$p->id] ?? null;
            $outToday = (int) ($outByProduct[$p->id] ?? 0);

            // Display available during the selected day: opening + in of that day,
            // or carried-over closing from previous days when no row exists yet.
            $displayStart = $day
                ? (int) $day->opening_qty + (int) $day->in_qty
                : (int) ($prevClosings[$p->id] ?? 0);

            $sisa = $day ? (int) $day->closing_qty : $displayStart;

            return [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'unit' => $p->unit,
                'min_stock' => $p->min_stock,
                'status' => $p->status,
                'main_qty' => (int) ($p->main_qty ?? 0),
                'display_qty' => $displayStart,
                'out_today' => $outToday,
                'sisa_display' => $sisa,
                'stock_status' => $sisa <= 0 ? 'OUT_OF_STOCK' : ($sisa <= $p->min_stock ? 'LOW_STOCK' : 'OK'),
            ];
        });

        $cards = [
            'total_products' => Product::where('status', 'active')->count(),
            'total_main_stock' => (int) MainStock::query()->sum('qty'),
            'total_display_stock' => (int) $rows->sum('sisa_display'),
            'out_today' => (int) $outByProduct->sum(),
            'low_stock' => $rows->where('stock_status', 'LOW_STOCK')->count(),
            'out_of_stock' => $rows->where('stock_status', 'OUT_OF_STOCK')->count(),
        ];

        $movement = $this->movementSeries($date->copy()->subDays(6), $date);

        return Inertia::render('Dashboard', [
            'date' => $date->toDateString(),
            'search' => $search,
            'cards' => $cards,
            'rows' => $rows,
            'movement' => $movement,
        ]);
    }

    protected function movementSeries(Carbon $from, Carbon $to): array
    {
        $dates = [];
        for ($d = $from->copy(); $d <= $to; $d->addDay()) {
            $dates[] = $d->toDateString();
        }

        $sumByType = fn (array $types) => StockHistory::selectRaw('trx_date, COALESCE(SUM(qty),0) as total')
            ->whereIn('type', $types)
            ->whereBetween('trx_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('trx_date')
            ->pluck('total', 'trx_date');

        $stockIn = $sumByType(['INITIAL_STOCK', 'STOCK_IN']);
        $transfer = $sumByType(['STOCK_TRANSFER']);
        $out = $sumByType(['OUT']);

        return [
            'dates' => $dates,
            'stock_in' => array_map(fn ($d) => (int) ($stockIn[$d] ?? 0), $dates),
            'transfer' => array_map(fn ($d) => (int) ($transfer[$d] ?? 0), $dates),
            'out' => array_map(fn ($d) => (int) ($out[$d] ?? 0), $dates),
        ];
    }
}
