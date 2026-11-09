<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StockOutsTemplate implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new GenericTemplateSheet('Data', ['SKU Produk', 'Qty', 'Tanggal', 'Keterangan'], [
                ['PRD001', 3, '2026-11-09', 'Terjual'],
                ['PRD002', 2, '2026-11-09', ''],
            ]),
            new TemplateNotesSheet([
                'Baris pertama adalah header — jangan diubah atau dihapus.',
                'SKU Produk: gunakan SKU yang sudah ada di Master Produk.',
                'Qty: angka bulat minimal 1. Qty tidak boleh melebihi Stock Display yang tersedia.',
                'Tanggal: format YYYY-MM-DD (contoh 2026-11-09). Tidak boleh tanggal di masa depan.',
                'Keterangan: opsional, maks 255 karakter.',
                'Setiap baris diproses sebagai transaksi OUT (keluar dari Stock Display) dengan nomor transaksi otomatis (OUT-YYYYMMDD-NNN).',
                'Baris yang gagal divalidasi (stok kurang, SKU tidak ditemukan, dll) akan dilaporkan di akhir proses.',
            ]),
        ];
    }
}