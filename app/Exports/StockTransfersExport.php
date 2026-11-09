<?php

namespace App\Exports;

use App\Exports\Concerns\ExportStyles;
use App\Models\StockTransfer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class StockTransfersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use ExportStyles;

    public function __construct(
        private string $search = '',
        private string $from = '',
        private string $to = '',
    ) {
    }

    public function collection(): Collection
    {
        return StockTransfer::query()
            ->with(['product:id,sku,name,unit', 'user:id,name'])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('trx_no', 'like', "%{$this->search}%")
                ->orWhereHas('product', fn ($p) => $p
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%"))))
            ->when($this->from !== '', fn ($q) => $q->whereDate('transfer_date', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('transfer_date', '<=', $this->to))
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->get();
    }

    public function headings(): array
    {
        return ['No Transfer', 'Tanggal', 'SKU', 'Produk', 'Qty', 'Dari', 'Ke', 'User', 'Keterangan'];
    }

    public function map($transfer): array
    {
        return [
            $transfer->trx_no,
            $transfer->transfer_date->format('Y-m-d'),
            $transfer->product?->sku,
            $transfer->product?->name,
            $transfer->qty,
            $transfer->from_location,
            $transfer->to_location,
            $transfer->user?->name,
            $transfer->note,
        ];
    }
}