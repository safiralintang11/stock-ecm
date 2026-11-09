<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateNotesSheet implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(
        private array $notes,
        private string $title = 'Petunjuk',
    ) {
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        return array_map(fn (string $note) => [$note], $this->notes);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FF1E293B']]],
            'A' => ['alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true]],
        ];
    }
}