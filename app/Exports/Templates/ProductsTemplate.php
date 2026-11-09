<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductsTemplate implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new GenericTemplateSheet('Data', ['Nama Produk', 'SKU', 'Isi Carton', 'Satuan', 'Min Stock', 'Status'], [
                ['Contoh Produk A', 'PRD001', 24, 'PCS', 10, 'active'],
                ['Contoh Produk B', 'PRD002', 12, 'PCS', 5, 'inactive'],
            ]),
            new TemplateNotesSheet([
                'Baris pertama adalah header — jangan diubah atau dihapus.',
                'Isi satu baris per produk. Baris contoh boleh dihapus.',
                'SKU: kode unik produk (maks 50 karakter). Jika SKU sudah ada di sistem, data produk tersebut akan DIPERBARUI. Jika belum ada, produk baru akan DIBUAT.',
                'Isi Carton: angka bulat minimal 1.',
                'Satuan: contoh "PCS", "BOTOL", "BOX", dll.',
                'Min Stock: angka bulat minimal 0.',
                'Status: active (aktif) atau inactive (nonaktif).',
                'Hanya file .xlsx, .xls, atau .csv yang dapat diupload.',
            ]),
        ];
    }
}