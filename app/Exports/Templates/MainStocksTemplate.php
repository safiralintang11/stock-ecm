<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MainStocksTemplate implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new GenericTemplateSheet('Saldo Stock Utama', ['Produk', 'SKU', 'Satuan', 'Stock Utama', 'Stock Display'], [
                ['Contoh Produk A', 'PRD001', 'PCS', 50, 0],
                ['Contoh Produk B', 'PRD002', 'PCS', 0, 0],
            ]),
            new TemplateNotesSheet([
                'Baris pertama adalah header — jangan diubah atau dihapus.',
                'Format kolom sama dengan sheet Saldo Stock Utama pada Export Stock Utama.',
                'SKU: gunakan SKU yang sudah terdaftar. Jika belum ada, import Master Produk terlebih dahulu.',
                'Edit Stock Utama dan/atau Stock Display. Nilai menjadi saldo akhir persis (PCS), bukan tambahan; angka 0 valid.',
                'Produk, SKU, dan Satuan hanya sebagai identitas produk.',
                'Import file export akan membaca sheet Saldo Stock Utama saja dan mengabaikan sheet histori.',
                'Sistem menghitung selisih dan mencatat Stock Adjustment secara atomik; saldo harian display dihitung ulang. Tanggalnya otomatis tanggal import.',
                'Baris yang gagal divalidasi akan dilaporkan setelah import.',
            ]),
        ];
    }
}