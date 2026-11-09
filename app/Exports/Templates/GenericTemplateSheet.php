<?php

namespace App\Exports\Templates;

use App\Exports\Concerns\ExportStyles;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

class GenericTemplateSheet implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    use ExportStyles;

    public function __construct(
        private string $title,
        private array $headings,
        private array $rows,
    ) {
    }

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }
}