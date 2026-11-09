<?php

namespace App\Http\Controllers\Concerns;

use App\Imports\BaseImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;

trait HandlesExcelImport
{
    /**
     * Run the import, returning an error message when the file cannot be parsed.
     */
    protected function importFile(BaseImport $import, UploadedFile $file): ?string
    {
        try {
            Excel::import($import, $file);

            return null;
        } catch (\Throwable) {
            return 'File tidak dapat dibaca. Pastikan file dalam format .xlsx, .xls, atau .csv.';
        }
    }

    protected function importRedirect(BaseImport $import, string $successMessage): RedirectResponse
    {
        $failures = $import->failures;

        if ($import->successCount === 0 && count($failures) > 0) {
            return redirect()->back()->with('error', 'Tidak ada baris yang berhasil diimport. '.$this->formatFailures($failures));
        }

        $message = $successMessage;

        if (count($failures) > 0) {
            $message .= ' '.$this->formatFailures($failures);
        }

        return redirect()->back()->with('success', $message);
    }

    protected function formatFailures(array $failures, int $limit = 5): string
    {
        $total = count($failures);
        $parts = array_map(
            fn (array $f) => "Baris {$f['row']}: {$f['message']}",
            array_slice($failures, 0, $limit)
        );

        $summary = implode(' | ', $parts);

        if ($total > $limit) {
            $summary .= " | ... dan {$total} baris lainnya";
        }

        return "({$total} baris gagal: {$summary})";
    }
}