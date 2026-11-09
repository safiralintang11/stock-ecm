<?php

namespace App\Imports;

use App\Models\User;
use App\Services\Stock\SisaDisplayService;
use Illuminate\Validation\ValidationException;

class SisaDisplayImport extends BaseImport
{
    protected SisaDisplayService $sisaDisplayService;

    public function __construct(User $user, SisaDisplayService $sisaDisplayService)
    {
        parent::__construct($user);
        $this->sisaDisplayService = $sisaDisplayService;
    }

    protected function processRow(mixed $row): void
    {
        $product = $this->productBySku($this->strCell($row, 'sku_produk'));
        $sisa = $this->intCell($row, 'sisa_display');
        $date = $this->requireDate($row);
        $note = $this->strCell($row, 'keterangan');

        if ($sisa === null || $sisa < 0) {
            throw ValidationException::withMessages(['sisa_display' => 'Sisa display harus angka bulat minimal 0.']);
        }

        $this->sisaDisplayService->execute($product, $sisa, $date, $this->user, $note);
    }
}