<?php

namespace App\Imports\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

trait RowHelpers
{
    /**
     * Read a cell by its heading-slug key; Excel often returns null for empty cells.
     */
    protected function cell(mixed $row, string $key): mixed
    {
        return $row[$key] ?? null;
    }

    protected function hasCell(mixed $row, string $key): bool
    {
        if ($row instanceof Collection) {
            return $row->has($key);
        }

        return is_array($row) && array_key_exists($key, $row);
    }

    protected function intCell(mixed $row, string $key): ?int
    {
        $value = $this->cell($row, $key);

        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        if (! is_finite($number) || floor($number) !== $number) {
            return null;
        }

        return (int) $number;
    }

    protected function strCell(mixed $row, string $key): ?string
    {
        $value = $this->cell($row, $key);

        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }

    /**
     * Accepts 'YYYY-MM-DD', 'YYYY/MM/DD', Excel serial numbers, or already-parsed dates.
     * Returns null when the value is empty or unparseable.
     */
    protected function dateCell(mixed $row, string $key): ?Carbon
    {
        $value = $this->cell($row, $key);

        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Skip rows that are entirely empty (users often leave trailing rows).
     */
    protected function isEmptyRow(mixed $row): bool
    {
        return collect($row)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->isEmpty();
    }
}