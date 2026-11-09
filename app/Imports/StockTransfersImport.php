<?php

namespace App\Imports;

use App\Models\User;
use App\Services\Stock\StockTransferService;
use Illuminate\Validation\ValidationException;

class StockTransfersImport extends BaseImport
{
    protected StockTransferService $transferService;

    public function __construct(User $user, StockTransferService $transferService)
    {
        parent::__construct($user);
        $this->transferService = $transferService;
    }

    protected function processRow(mixed $row): void
    {
        $product = $this->productBySku($this->strCell($row, 'sku_produk'));
        $qty = $this->intCell($row, 'qty');
        $date = $this->requireDate($row);
        $note = $this->strCell($row, 'keterangan');

        if ($qty === null || $qty < 1) {
            throw ValidationException::withMessages(['qty' => 'Qty harus angka bulat lebih dari 0.']);
        }

        $this->transferService->execute($product, $qty, $date, $this->user, $note);
    }
}