<?php

namespace App\Exports;

use App\Exports\Concerns\ExportStyles;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    use ExportStyles;

    public function __construct(
        private string $search = '',
        private string $status = '',
    ) {
    }

    public function collection(): Collection
    {
        return Product::query()
            ->withBalances()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return ['Nama Produk', 'SKU', 'Isi Carton', 'Satuan', 'Min Stock', 'Status', 'Stock Utama', 'Stock Display'];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 36,
            'B' => 20,
            'C' => 14,
            'D' => 14,
            'E' => 14,
            'F' => 14,
            'G' => 18,
            'H' => 18,
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->sku,
            $product->carton_qty,
            $product->unit,
            $product->min_stock,
            $product->status,
            (int) ($product->main_qty ?? 0),
            (int) ($product->display_qty ?? 0),
        ];
    }
}