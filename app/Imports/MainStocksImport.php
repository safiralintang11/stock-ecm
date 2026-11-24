<?php

namespace App\Imports;

use App\Models\User;
use App\Services\Stock\StockAdjustmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MainStocksImport extends BaseImport implements SkipsUnknownSheets, WithMultipleSheets
{
    protected StockAdjustmentService $adjustmentService;

    public function __construct(User $user, StockAdjustmentService $adjustmentService)
    {
        parent::__construct($user);
        $this->adjustmentService = $adjustmentService;
    }

    public function sheets(): array
    {
        return [
            'Saldo Stock Utama' => $this,
            'Data' => $this,
            'Worksheet' => $this,
        ];
    }

    public function onUnknownSheet(string|int $sheetName): void
    {
    }

    protected function processRow(mixed $row): void
    {
        $product = $this->productBySku(
            $this->strCell($row, 'sku') ?? $this->strCell($row, 'sku_produk')
        );
        $target = $this->intCell($row, 'stock_utama')
            ?? $this->intCell($row, 'saldo_akhir')
            ?? $this->intCell($row, 'qty');
        $hasDisplayTarget = $this->hasCell($row, 'stock_display');
        $displayTarget = $hasDisplayTarget ? $this->intCell($row, 'stock_display') : null;
        $dateValue = $this->cell($row, 'tanggal');
        $date = $dateValue === null || $dateValue === ''
            ? today()
            : $this->requireDate($row);
        $reason = $this->strCell($row, 'keterangan') ?: 'Penyesuaian saldo melalui import Excel Stock Utama';

        if ($target === null || $target < 0) {
            throw ValidationException::withMessages(['stock_utama' => 'Stock Utama wajib berupa angka bulat minimal 0.']);
        }

        if ($hasDisplayTarget && ($displayTarget === null || $displayTarget < 0)) {
            throw ValidationException::withMessages(['stock_display' => 'Stock Display wajib berupa angka bulat minimal 0.']);
        }

        DB::transaction(function () use ($product, $target, $displayTarget, $hasDisplayTarget, $reason, $date) {
            $this->adjustmentService->setBalance($product, 'main', $target, $reason, $date, $this->user);

            if ($hasDisplayTarget) {
                $this->adjustmentService->setBalance($product, 'display', $displayTarget, $reason, $date, $this->user);
            }
        });
    }
}