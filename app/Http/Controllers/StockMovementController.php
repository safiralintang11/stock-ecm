<?php

namespace App\Http\Controllers;

use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $from = Carbon::parse($request->input('from_date', today()->subDays(6)->toDateString()));
        $to = Carbon::parse($request->input('to_date', today()->toDateString()));

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

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
        $adjustment = $sumByType(['ADJUSTMENT']);

        $series = [
            'dates' => $dates,
            'stock_in' => array_map(fn ($d) => (int) ($stockIn[$d] ?? 0), $dates),
            'transfer' => array_map(fn ($d) => (int) ($transfer[$d] ?? 0), $dates),
            'out' => array_map(fn ($d) => (int) ($out[$d] ?? 0), $dates),
            'adjustment' => array_map(fn ($d) => (int) ($adjustment[$d] ?? 0), $dates),
        ];

        return Inertia::render('Reports/StockMovement', [
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'series' => $series,
        ]);
    }
}
