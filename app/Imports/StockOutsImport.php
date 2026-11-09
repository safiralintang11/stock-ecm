<?php

namespace App\Imports;

use App\Models\User;
use App\Services\Stock\StockOutService;
use Illuminate\Validation\ValidationException;

class StockOutsImport extends BaseImport
{
    protected StockOutService $outService;

    public function __construct(User $user, StockOutService $outService)
    {
        parent::__construct($user);
        $this->outService = $outService;
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

        $this->outService->execute($product, $qty, $date, $this->user, $note);
    }
}