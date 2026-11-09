<?php

namespace App\Exports;

use App\Exports\Concerns\ExportStyles;
use App\Models\DisplayStock;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class DailyStockExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use ExportStyles;

    public function __construct(
        private string $date = '',
        private string $search = '',
    ) {
        $this->date = $this->date ?: today()->toDateString();
    }

    public function collection(): Collection
    {
        $date = Carbon::parse($this->date);

        $products = Product::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")))
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

        return $products->map(function (Product $p) use ($date, $dayRows, $prevClosings) {
            $day = $dayRows[$p->id] ?? null;

            return [
                'sku' => $p->sku,
                'name' => $p->name,
                'opening_qty' => $day ? (int) $day->opening_qty : (int) ($prevClosings[$p->id] ?? 0),
                'in_qty' => $day ? (int) $day->in_qty : 0,
                'out_qty' => $day ? (int) $day->out_qty : 0,
                'adjust_qty' => $day ? (int) $day->adjust_qty : 0,
                'closing_qty' => $day ? (int) $day->closing_qty : (int) ($prevClosings[$p->id] ?? 0),
            ];
        });
    }

    public function headings(): array
    {
        return ['SKU', 'Produk', 'Opening', 'In', 'Out', 'Adjust', 'Closing'];
    }

    public function map($row): array
    {
        return [
            $row['sku'],
            $row['name'],
            $row['opening_qty'],
            $row['in_qty'],
            $row['out_qty'],
            $row['adjust_qty'],
            $row['closing_qty'],
        ];
    }
}