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
        $qty = $this->intCell($row, 'qty');
        $reason = $this->strCell($row, 'alasan');
        $date = $this->requireDate($row);

        if (! in_array($scope, ['main', 'display'])) {
            throw ValidationException::withMessages(['scope' => 'Scope harus "main" atau "display".']);
        }

        if ($qty === null || $qty === 0) {
            throw ValidationException::withMessages(['qty' => 'Qty penyesuaian tidak boleh 0 (positif = tambah, negatif = kurang).']);
        }

        if ($reason === null || $reason === '') {
            throw ValidationException::withMessages(['alasan' => 'Alasan penyesuaian wajib diisi.']);
        }

        $this->adjustmentService->execute($product, $scope, $qty, $reason, $date, $this->user);
    }
}