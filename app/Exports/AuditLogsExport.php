<?php

namespace App\Exports;

use App\Exports\Concerns\ExportStyles;
use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class AuditLogsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use ExportStyles;

    public function __construct(
        private string $search = '',
        private string $module = '',
        private string $userId = '',
        private string $from = '',
        private string $to = '',
    ) {
    }

    public function collection(): Collection
    {
        return AuditLog::query()
            ->with('user:id,name')
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('action', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")
                ->orWhere('reason', 'like', "%{$this->search}%")))
            ->when($this->module !== '', fn ($q) => $q->where('module', $this->module))
            ->when($this->userId !== '', fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->from !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->orderByDesc('id')
            ->get();
    }

    public function headings(): array
    {
        return ['Waktu', 'User', 'Aksi', 'Modul', 'SKU', 'Alasan', 'IP Address', 'Data Sebelum', 'Data Sesudah'];
    }

    public function map($log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->user?->name,
            $log->action,
            $log->module,
            $log->sku,
            $log->reason,
            $log->ip_address,
            $log->before !== null ? json_encode($log->before) : null,
            $log->after !== null ? json_encode($log->after) : null,
        ];
    }
}