<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SisaDisplayTemplate implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new GenericTemplateSheet('Data', ['SKU Produk', 'Sisa Display', 'Tanggal', 'Keterangan'], [
                ['PRD001', 7, '2026-11-09', 'Hasil hitung fisik'],
                ['PRD002', 3, '2026-11-09', ''],
            ]),
            new TemplateNotesSheet([
                'Baris pertama adalah header — jangan diubah atau dihapus.',
                'SKU Produk: gunakan SKU yang sudah ada di Master Produk.',
                'Sisa Display: hasil hitung fisik stok di display, angka bulat minimal 0. Tidak boleh melebihi Stock Display yang tercatat di sistem.',
                'Tanggal: format YYYY-MM-DD (contoh 2026-11-09). Tidak boleh tanggal di masa depan.',
                'Keterangan: opsional, maks 255 karakter.',
                'Selisih antara stok display sistem dengan sisa fisik otomatis dicatat sebagai transaksi OUT.',
                'Jika tidak ada selisih, baris tetap dianggap berhasil (tidak ada OUT yang dicatat).',
            ]),
        ];
    }
}