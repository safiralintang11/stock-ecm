<?php

namespace App\Exports;

use App\Exports\Concerns\ExportStyles;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class SisaDisplayExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use ExportStyles;

    public function __construct(private string $search = '')
    {
    }

    public function collection(): Collection
    {
        return Product::query()
            ->withBalances()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return ['SKU', 'Produk', 'Satuan', 'Stock Display', 'Status'];
    }

    public function map($product): array
    {
        $display = (int) ($product->display_qty ?? 0);

        return [
            $product->sku,
            $product->name,
            $product->unit,
            $display,
            $display <= 0 ? 'OUT_OF_STOCK' : ($display <= $product->min_stock ? 'LOW_STOCK' : 'OK'),
        ];
    }
}