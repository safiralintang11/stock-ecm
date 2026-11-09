<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\ExportStyles;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

class MainStockBalanceSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    use ExportStyles;

    public function __construct(private string $search = '')
    {
    }

    public function title(): string
    {
        return 'Saldo Stock Utama';
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
        return ['Produk', 'SKU', 'Satuan', 'Stock Utama', 'Stock Display'];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->sku,
            $product->unit,
            (int) ($product->main_qty ?? 0),
            (int) ($product->display_qty ?? 0),
        ];
    }
}