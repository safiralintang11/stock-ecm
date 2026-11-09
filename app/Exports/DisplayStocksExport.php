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

class DisplayStocksExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
            ->withBalances()
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
            $displayQty = $day ? (int) $day->closing_qty : (int) ($prevClosings[$p->id] ?? 0);

            return [
                'sku' => $p->sku,
                'name' => $p->name,
                'unit' => $p->unit,
                'carton_qty' => $p->carton_qty,
                'min_stock' => $p->min_stock,
                'main_qty' => (int) ($p->main_qty ?? 0),
                'total_received' => (int) ($p->total_received ?? 0),
                'opening_qty' => $day ? (int) $day->opening_qty : null,
                'in_qty' => $day ? (int) $day->in_qty : 0,
                'out_qty' => $day ? (int) $day->out_qty : 0,
                'closing_qty' => $displayQty,
                'status' => $displayQty <= 0 ? 'OUT_OF_STOCK' : ($displayQty <= $p->min_stock ? 'LOW_STOCK' : 'OK'),
            ];
        });
    }

    public function headings(): array
    {
        return ['SKU', 'Nama Produk', 'Satuan', 'Isi Carton', 'Min Stock', 'Stock Utama', 'Total Received', 'Opening', 'In', 'Out', 'Closing', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row['sku'],
            $row['name'],
            $row['unit'],
            $row['carton_qty'],
            $row['min_stock'],
            $row['main_qty'],
            $row['total_received'],
            $row['opening_qty'] ?? $row['closing_qty'],
            $row['in_qty'],
            $row['out_qty'],
            $row['closing_qty'],
            $row['status'],
        ];
    }
}