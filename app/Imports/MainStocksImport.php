<?php

namespace App\Imports;

use App\Models\User;
use App\Services\Stock\StockInService;
use Illuminate\Validation\ValidationException;

class MainStocksImport extends BaseImport
{
    protected StockInService $stockInService;

    public function __construct(User $user, StockInService $stockInService)
    {
        parent::__construct($user);
        $this->stockInService = $stockInService;
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

        $this->stockInService->execute($product, $qty, $date, $this->user, $note);
    }
}