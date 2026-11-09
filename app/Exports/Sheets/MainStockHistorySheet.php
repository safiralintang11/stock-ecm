<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\ExportStyles;
use App\Models\StockHistory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

class MainStockHistorySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    use ExportStyles;

    public function __construct(private string $search = '')
    {
    }

    public function title(): string
    {
        return 'Histori Input';
    }

    public function collection(): Collection
    {
        return StockHistory::query()
            ->whereIn('type', ['INITIAL_STOCK', 'STOCK_IN'])
            ->with(['product:id,sku,name,unit', 'user:id,name'])
            ->when($this->search !== '', fn ($q) => $q->whereHas('product', fn ($p) => $p
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")))
            ->orderByDesc('trx_date')
            ->orderByDesc('id')
            ->get();
    }

    public function headings(): array
    {
        return ['Tanggal', 'Produk', 'SKU', 'Jenis', 'Qty', 'User', 'Keterangan'];
    }

    public function map($history): array
    {
        return [
            $history->trx_date->format('Y-m-d'),
            $history->product?->name,
            $history->product?->sku,
            $history->type,
            $history->qty,
            $history->user?->name,
            $history->description,
        ];
    }
}