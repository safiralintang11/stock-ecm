<?php

namespace App\Exports;

use App\Exports\Concerns\ExportStyles;
use App\Models\StockHistory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class StockHistoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use ExportStyles;

    public function __construct(
        private string $search = '',
        private string $type = '',
        private string $from = '',
        private string $to = '',
    ) {
    }

    public function collection(): Collection
    {
        return StockHistory::query()
            ->with(['product:id,sku,name,unit', 'user:id,name'])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('reference', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")
                ->orWhereHas('product', fn ($p) => $p
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%"))))
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->when($this->from !== '', fn ($q) => $q->whereDate('trx_date', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('trx_date', '<=', $this->to))
            ->orderByDesc('trx_date')
            ->orderByDesc('id')
            ->get();
    }

    public function headings(): array
    {
        return ['Tanggal', 'Produk', 'SKU', 'Jenis', 'Qty', 'Utama Sebelum', 'Utama Sesudah', 'Display Sebelum', 'Display Sesudah', 'User', 'Referensi', 'Keterangan'];
    }

    public function map($history): array
    {
        return [
            $history->trx_date->format('Y-m-d'),
            $history->product?->name,
            $history->product?->sku,
            $history->type,
            $history->qty,
            $history->main_before,
            $history->main_after,
            $history->display_before,
            $history->display_after,
            $history->user?->name,
            $history->reference,
            $history->description,
        ];
    }
}