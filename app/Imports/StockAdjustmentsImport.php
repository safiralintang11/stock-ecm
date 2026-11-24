<?php

namespace App\Imports;

use App\Models\User;
use App\Services\Stock\StockAdjustmentService;
use Illuminate\Validation\ValidationException;

class StockAdjustmentsImport extends BaseImport
{
    protected StockAdjustmentService $adjustmentService;

    public function __construct(User $user, StockAdjustmentService $adjustmentService)
    {
        parent::__construct($user);
        $this->adjustmentService = $adjustmentService;
    }

    protected function processRow(mixed $row): void
    {
        $product = $this->productBySku($this->strCell($row, 'sku_produk'));
        $scope = $this->strCell($row, 'scope');
        $target = $this->intCell($row, 'saldo_akhir') ?? $this->intCell($row, 'qty');
        $reason = $this->strCell($row, 'alasan');
        $date = $this->requireDate($row);

        if (! in_array($scope, ['main', 'display'])) {
            throw ValidationException::withMessages(['scope' => 'Scope harus "main" atau "display".']);
        }

        if ($target === null || $target < 0) {
            throw ValidationException::withMessages(['saldo_akhir' => 'Saldo akhir wajib berupa angka bulat minimal 0.']);
        }

        if ($reason === null || $reason === '') {
            throw ValidationException::withMessages(['alasan' => 'Alasan penyesuaian wajib diisi.']);
        }

        $this->adjustmentService->setBalance($product, $scope, $target, $reason, $date, $this->user);
    }
}