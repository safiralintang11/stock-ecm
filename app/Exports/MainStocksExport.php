<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MainStocksExport implements Export, WithMultipleSheets
{
    public function __construct(private string $search = '')
    {
    }

    public function sheets(): array
    {
        return [
            new Sheets\MainStockBalanceSheet($this->search),
            new Sheets\MainStockHistorySheet($this->search),
        ];
    }
}