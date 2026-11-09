<?php

namespace App\Exports;

use App\Exports\Concerns\ExportStyles;
use App\Models\StockOut;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class StockOutsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
        return StockOut::query()
            ->with(['product:id,sku,name,unit', 'user:id,name'])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('trx_no', 'like', "%{$this->search}%")
                ->orWhereHas('product', fn ($p) => $p
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%"))))
            ->when($this->from !== '', fn ($q) => $q->whereDate('out_date', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('out_date', '<=', $this->to))
            ->orderByDesc('out_date')
            ->orderByDesc('id')
            ->get();
    }

    public function headings(): array
    {
        return ['No OUT', 'Tanggal', 'SKU', 'Produk', 'Qty', 'User', 'Keterangan'];
    }

    public function map($out): array
    {
        return [
            $out->trx_no,
            $out->out_date->format('Y-m-d'),
            $out->product?->sku,
            $out->product?->name,
            $out->qty,
            $out->user?->name,
            $out->note,
        ];
    }
}