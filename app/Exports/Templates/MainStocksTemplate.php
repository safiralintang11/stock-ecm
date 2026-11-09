<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MainStocksTemplate implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new GenericTemplateSheet('Data', ['SKU Produk', 'Qty', 'Tanggal', 'Keterangan'], [
                ['PRD001', 50, '2026-11-09', 'Stok awal bulan'],
                ['PRD002', 24, '2026-11-09', ''],
            ]),
            new TemplateNotesSheet([
                'Baris pertama adalah header — jangan diubah atau dihapus.',
                'SKU Produk: gunakan SKU yang sudah ada di Master Produk. SKU yang tidak ditemukan akan gagal.',
                'Qty: angka bulat minimal 1 (satuan PCS).',
                'Tanggal: format YYYY-MM-DD (contoh 2026-11-09). Tidak boleh tanggal di masa depan.',
                'Keterangan: opsional, maks 255 karakter.',
                'Input pertama untuk suatu produk akan dicatat sebagai INITIAL STOCK, input berikutnya sebagai STOCK IN.',
                'Setiap baris divalidasi sesuai aturan sistem; baris yang gagal akan dilaporkan di akhir proses.',
            ]),
        ];
    }
}