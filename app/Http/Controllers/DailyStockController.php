<?php

namespace App\Http\Controllers;

use App\Models\DisplayStock;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DailyStockController extends Controller
{
    public function index(Request $request)
    {
        $date = Carbon::parse($request->input('date', today()->toDateString()));
        $search = trim((string) $request->input('search', ''));

        $products = Product::query()
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

            return [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'unit' => $p->unit,
                'opening_qty' => $day ? (int) $day->opening_qty : (int) ($prevClosings[$p->id] ?? 0),
                'in_qty' => $day ? (int) $day->in_qty : 0,
                'out_qty' => $day ? (int) $day->out_qty : 0,
                'adjust_qty' => $day ? (int) $day->adjust_qty : 0,
                'closing_qty' => $day ? (int) $day->closing_qty : (int) ($prevClosings[$p->id] ?? 0),
                'has_activity' => (bool) $day,
            ];
        });

        return Inertia::render('Reports/DailyStock', [
            'date' => $date->toDateString(),
            'search' => $search,
            'rows' => $rows,
        ]);
    }
}
