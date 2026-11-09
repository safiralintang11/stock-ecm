<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DisplayStocksTemplate implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new GenericTemplateSheet('Data', ['SKU Produk', 'Qty', 'Tanggal', 'Keterangan'], [
                ['PRD001', 10, '2026-11-09', 'Tambah display'],
                ['PRD002', 5, '2026-11-09', ''],
            ]),
            new TemplateNotesSheet([
                'Baris pertama adalah header — jangan diubah atau dihapus.',
                'Data Stock Display harian (Opening / In / Out / Closing) dihitung OTOMATIS oleh sistem dari transaksi (Stock Transfer, OUT, Adjustment) dan tidak dapat diimport langsung.',
                'Import pada halaman ini menambah stok ke display: setiap baris dicatat sebagai Stock Transfer (Stock Utama → Stock Display) dengan nomor transaksi otomatis (ST-YYYYMMDD-NNN).',
                'SKU Produk: gunakan SKU yang sudah ada di Master Produk.',
                'Qty: angka bulat minimal 1. Qty tidak boleh melebihi Stock Utama yang tersedia.',
                'Tanggal: format YYYY-MM-DD (contoh 2026-11-09). Tidak boleh tanggal di masa depan.',
                'Keterangan: opsional, maks 255 karakter.',
                'Baris yang gagal divalidasi (stok utama kurang, SKU tidak ditemukan, dll) akan dilaporkan di akhir proses.',
            ]),
        ];
    }
}