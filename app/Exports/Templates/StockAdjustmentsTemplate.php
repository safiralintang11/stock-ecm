<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StockAdjustmentsTemplate implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new GenericTemplateSheet('Data', ['SKU Produk', 'Scope', 'Saldo Akhir', 'Alasan', 'Tanggal'], [
                ['PRD001', 'display', 0, 'Hasil hitung fisik', '2026-11-09'],
                ['PRD002', 'main', 10, 'Koreksi saldo fisik', '2026-11-09'],
            ]),
            new TemplateNotesSheet([
                'Baris pertama adalah header — jangan diubah atau dihapus.',
                'SKU Produk: gunakan SKU yang sudah ada di Master Produk. Jika belum ada, import Master Produk terlebih dahulu.',
                'Scope: "main" (Stock Utama) atau "display" (Stock Display).',
                'Saldo Akhir: angka bulat minimal 0. Nilai ini menjadi saldo akhir persis, bukan delta; angka 0 valid.',
                'Sistem menghitung selisih dari saldo saat ini dan mencatatnya sebagai adjustment. Jika saldo sudah sama, adjustment nol tetap dicatat.',
                'Alasan: wajib diisi, maks 255 karakter (contoh: Barang rusak, salah hitung, dll).',
                'Tanggal: format YYYY-MM-DD (contoh 2026-11-09). Tidak boleh tanggal di masa depan.',
            ]),
        ];
    }
}