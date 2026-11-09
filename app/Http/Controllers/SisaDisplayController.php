<?php

namespace App\Http\Controllers;

use App\Exports\SisaDisplayExport;
use App\Exports\Templates\SisaDisplayTemplate;
use App\Http\Controllers\Concerns\HandlesExcelImport;
use App\Http\Requests\SisaDisplayRequest;
use App\Imports\SisaDisplayImport;
use App\Models\Product;
use App\Services\Stock\SisaDisplayService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SisaDisplayController extends Controller
{
    use HandlesExcelImport;

    public function __construct(
        protected SisaDisplayService $service,
    ) {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $rows = Product::active()
            ->withBalances()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'unit' => $p->unit,
                'display_qty' => (int) ($p->display_qty ?? 0),
                'stock_status' => ($p->display_qty ?? 0) <= 0
                    ? 'OUT_OF_STOCK'
                    : (($p->display_qty ?? 0) <= $p->min_stock ? 'LOW_STOCK' : 'OK'),
            ]);

        return Inertia::render('SisaDisplay/Index', [
            'rows' => $rows,
            'search' => $search,
        ]);
    }

    public function store(SisaDisplayRequest $request)
    {
        $product = Product::findOrFail($request->product_id);

        $out = $this->service->execute(
            $product,
            (int) $request->qty,
            Carbon::parse($request->trx_date),
            $request->user(),
            $request->note
        );

        if (! $out) {
            return back()->with('success', "Sisa display {$product->name} sesuai dengan sistem — tidak ada selisih, OUT tidak dicatat.");
        }

        return back()->with('success', "Selisih dicatat sebagai OUT {$out->trx_no}: {$out->qty} {$product->unit} keluar dari Stock Display.");
    }

    public function export(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        return Excel::download(
            new SisaDisplayExport($search),
            'sisa-display-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(new SisaDisplayTemplate(), 'template-sisa-display.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new SisaDisplayImport($request->user(), $this->service);
        $error = $this->importFile($import, $request->file('file'));

        if ($error) {
            return redirect()->back()->with('error', $error);
        }

        return $this->importRedirect($import, "Import Sisa Display selesai: {$import->successCount} baris berhasil diproses.");
    }
}