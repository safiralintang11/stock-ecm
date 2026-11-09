<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StockAdjustmentsTemplate implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new GenericTemplateSheet('Data', ['SKU Produk', 'Scope', 'Qty', 'Alasan', 'Tanggal'], [
                ['PRD001', 'display', -2, 'Barang rusak', '2026-11-09'],
                ['PRD002', 'main', 10, 'Salah input stok awal', '2026-11-09'],
            ]),
            new TemplateNotesSheet([
                'Baris pertama adalah header — jangan diubah atau dihapus.',
                'SKU Produk: gunakan SKU yang sudah ada di Master Produk.',
                'Scope: "main" (Stock Utama) atau "display" (Stock Display).',
                'Qty: angka penyesuaian bertanda, positif (+) menambah stok, negatif (-) mengurangi stok. Tidak boleh 0.',
                'Alasan: wajib diisi, maks 255 karakter (contoh: Barang rusak, salah hitung, dll).',
                'Tanggal: format YYYY-MM-DD (contoh 2026-11-09). Tidak boleh tanggal di masa depan.',
                'Hasil akhir stok tidak boleh minus — baris dengan hasil minus akan gagal.',
            ]),
        ];
    }
}