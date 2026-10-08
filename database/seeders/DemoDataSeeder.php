<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Services\Stock\StockInService;
use App\Services\Stock\StockOutService;
use App\Services\Stock\StockTransferService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $novan = User::where('email', 'novan@stockecm.test')->first();
        $today = Carbon::today();

        $products = [
            ['name' => 'Minyak Goreng 1L', 'sku' => 'PRD001', 'carton_qty' => 24, 'unit' => 'PCS', 'min_stock' => 20, 'in' => 500, 'transfer' => 50, 'out' => 10],
            ['name' => 'Gula Pasir 1kg', 'sku' => 'PRD002', 'carton_qty' => 12, 'unit' => 'PCS', 'min_stock' => 10, 'in' => 300, 'transfer' => 40, 'out' => 8],
            ['name' => 'Teh Celup Box', 'sku' => 'PRD003', 'carton_qty' => 48, 'unit' => 'PCS', 'min_stock' => 15, 'in' => 200, 'transfer' => 30, 'out' => 5],
            ['name' => 'Kopi Sachet', 'sku' => 'PRD004', 'carton_qty' => 6, 'unit' => 'PCS', 'min_stock' => 10, 'in' => 150, 'transfer' => 12, 'out' => 3],
            ['name' => 'Mie Instan', 'sku' => 'PRD005', 'carton_qty' => 40, 'unit' => 'PCS', 'min_stock' => 5, 'in' => 100, 'transfer' => 10, 'out' => 10],
        ];

        foreach ($products as $row) {
            $product = Product::updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'name' => $row['name'],
                    'carton_qty' => $row['carton_qty'],
                    'unit' => $row['unit'],
                    'min_stock' => $row['min_stock'],
                    'status' => 'active',
                ]
            );

            if ($row['in'] > 0) {
                app(StockInService::class)->execute($product, $row['in'], $today, $novan, 'Stok awal gudang');
            }

            if ($row['transfer'] > 0) {
                app(StockTransferService::class)->execute($product, $row['transfer'], $today, $novan, 'Stock display awal');
            }

            if ($row['out'] > 0) {
                app(StockOutService::class)->execute($product, $row['out'], $today, $novan, 'Kebutuhan harian');
            }
        }
    }
}
