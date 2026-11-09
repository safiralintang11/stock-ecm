<?php

namespace App\Exports;

use App\Exports\Concerns\ExportStyles;
use App\Models\StockAdjustment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class StockAdjustmentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use ExportStyles;

    public function __construct(
        private string $search = '',
        private string $scope = '',
    ) {
    }

    public function collection(): Collection
    {
        return StockAdjustment::query()
            ->with(['product:id,sku,name,unit', 'user:id,name'])
            ->when($this->search !== '', fn ($q) => $q->whereHas('product', fn ($p) => $p
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")))
            ->when($this->scope !== '', fn ($q) => $q->where('scope', $this->scope))
            ->orderByDesc('adjust_date')
            ->orderByDesc('id')
            ->get();
    }

    public function headings(): array
    {
        return ['Tanggal', 'SKU', 'Produk', 'Scope', 'Qty', 'Stok Sebelum', 'Stok Sesudah', 'Alasan', 'User'];
    }

    public function map($adjustment): array
    {
        return [
            $adjustment->adjust_date->format('Y-m-d'),
            $adjustment->product?->sku,
            $adjustment->product?->name,
            $adjustment->scope,
            $adjustment->qty,
            $adjustment->stock_before,
            $adjustment->stock_after,
            $adjustment->reason,
            $adjustment->user?->name,
        ];
    }
}