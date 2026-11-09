<?php

namespace App\Exports;

use App\Exports\Concerns\ExportStyles;
use App\Models\StockHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class StockMovementExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use ExportStyles;

    public function __construct(
        private string $from = '',
        private string $to = '',
    ) {
    }

    public function collection(): Collection
    {
        $from = Carbon::parse($this->from ?: today()->subDays(6)->toDateString());
        $to = Carbon::parse($this->to ?: today()->toDateString());

        if ($from > $to) {
            [$from, $to] = [$to, $from];
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

        $rows = [];
        for ($d = $from->copy(); $d <= $to; $d->addDay()) {
            $key = $d->toDateString();
            $rows[] = [
                'date' => $key,
                'stock_in' => (int) ($stockIn[$key] ?? 0),
                'transfer' => (int) ($transfer[$key] ?? 0),
                'out' => (int) ($out[$key] ?? 0),
                'adjustment' => (int) ($adjustment[$key] ?? 0),
            ];
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return ['Tanggal', 'Stock In', 'Transfer', 'OUT', 'Adjustment'];
    }

    public function map($row): array
    {
        return [
            $row['date'],
            $row['stock_in'],
            $row['transfer'],
            $row['out'],
            $row['adjustment'],
        ];
    }
}