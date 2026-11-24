<?php

namespace App\Imports;

use App\Imports\Concerns\RowHelpers;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

abstract class BaseImport implements ToCollection, WithHeadingRow
{
    use RowHelpers;

    /** @var int Jumlah baris yang berhasil diproses. */
    public int $successCount = 0;

    /** @var array<int, array{row: int, message: string, data: array<string, string>}> Baris yang gagal. */
    public array $failures = [];

    public function __construct(protected User $user)
    {
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $line = $i + 2; // baris ke-1 adalah header

            try {
                $this->processRow($row);
                $this->successCount++;
            } catch (ValidationException $e) {
                $this->failures[] = [
                    'row' => $line,
                    'message' => collect($e->errors())->flatten()->implode('; '),
                    'data' => $this->rowSnapshot($row),
                ];
            }
        }
    }

    abstract protected function processRow(mixed $row): void;

    /**
     * Ringkasan isi baris yang gagal, dipakai untuk laporan error yang bisa diunduh.
     *
     * @return array<string, string>
     */
    protected function rowSnapshot(mixed $row): array
    {
        return collect($row)
            ->map(fn ($value) => is_scalar($value) ? trim((string) $value) : '')
            ->filter(fn ($value) => $value !== '')
            ->take(15)
            ->all();
    }

    protected function productBySku(?string $sku): Product
    {
        if ($sku === null || $sku === '') {
            throw ValidationException::withMessages(['sku' => 'SKU Produk wajib diisi.']);
        }

        $product = Product::where('sku', $sku)->first();

        if (! $product) {
            throw ValidationException::withMessages([
                'sku' => "SKU '{$sku}' tidak ditemukan. Import Master Produk terlebih dahulu.",
            ]);
        }

        return $product;
    }

    protected function requireDate(mixed $row, string $key = 'tanggal'): Carbon
    {
        $date = $this->dateCell($row, $key);

        if (! $date) {
            throw ValidationException::withMessages([$key => 'Tanggal tidak valid — gunakan format YYYY-MM-DD.']);
        }

        if ($date->isFuture()) {
            throw ValidationException::withMessages([$key => 'Tanggal tidak boleh di masa depan.']);
        }

        return $date;
    }
}