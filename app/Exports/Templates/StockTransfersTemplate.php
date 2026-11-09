<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StockTransfersTemplate implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new GenericTemplateSheet('Data', ['SKU Produk', 'Qty', 'Tanggal', 'Keterangan'], [
                ['PRD001', 10, '2026-11-09', 'Pindah ke display'],
                ['PRD002', 5, '2026-11-09', ''],
            ]),
            new TemplateNotesSheet([
                'Baris pertama adalah header — jangan diubah atau dihapus.',
                'SKU Produk: gunakan SKU yang sudah ada di Master Produk.',
                'Qty: angka bulat minimal 1. Qty tidak boleh melebihi Stock Utama yang tersedia.',
                'Tanggal: format YYYY-MM-DD (contoh 2026-11-09). Tidak boleh tanggal di masa depan.',
                'Keterangan: opsional, maks 255 karakter.',
                'Setiap baris diproses sebagai transaksi Stock Transfer (Stock Utama → Stock Display) dengan nomor transaksi otomatis (ST-YYYYMMDD-NNN).',
                'Baris yang gagal divalidasi (stok kurang, SKU tidak ditemukan, dll) akan dilaporkan di akhir proses.',
            ]),
        ];
    }
}