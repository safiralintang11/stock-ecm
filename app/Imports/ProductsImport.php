<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductsImport extends BaseImport
{
    public int $created = 0;

    public int $updated = 0;

    protected function processRow(mixed $row): void
    {
        $data = [
            'name' => $this->strCell($row, 'nama_produk'),
            'sku' => $this->strCell($row, 'sku'),
            'carton_qty' => $this->intCell($row, 'isi_carton'),
            'unit' => $this->strCell($row, 'satuan'),
            'min_stock' => $this->intCell($row, 'min_stock'),
            'status' => $this->strCell($row, 'status') ?? 'active',
        ];

        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:50'],
            'carton_qty' => ['required', 'integer', 'min:1'],
            'unit' => ['required', 'string', 'max:20'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'sku.required' => 'SKU wajib diisi.',
            'name.required' => 'Nama produk wajib diisi.',
            'carton_qty.min' => 'Isi dalam 1 carton tidak boleh minus.',
            'min_stock.min' => 'Minimum stock tidak boleh minus.',
            'status.in' => 'Status harus active atau inactive.',
        ])->validate();

        $existing = Product::where('sku', $data['sku'])->first();

        if ($existing) {
            $existing->update($data);
            $this->updated++;
        } else {
            Product::create($data);
            $this->created++;
        }
    }
}