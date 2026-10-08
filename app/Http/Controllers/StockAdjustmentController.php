<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustmentRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\Stock\StockAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class StockAdjustmentController extends Controller
{
    public function __construct(protected StockAdjustmentService $adjustmentService)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $scope = $request->input('scope', '');

        $adjustments = StockAdjustment::query()
            ->with(['product:id,sku,name,unit', 'user:id,name'])
            ->when($search !== '', fn ($q) => $q->whereHas('product', fn ($p) => $p
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->when($scope !== '', fn ($q) => $q->where('scope', $scope))
            ->orderByDesc('adjust_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $products = Product::active()->orderBy('name')->get(['id', 'name', 'sku', 'unit', 'carton_qty']);

        return Inertia::render('StockAdjustments/Index', [
            'adjustments' => $adjustments,
            'products' => $products,
            'filters' => ['search' => $search, 'scope' => $scope],
        ]);
    }

    public function store(AdjustmentRequest $request)
    {
        $product = Product::findOrFail($request->product_id);

        $this->adjustmentService->execute(
            $product,
            $request->scope,
            (int) $request->qty,
            $request->reason,
            Carbon::parse($request->trx_date),
            $request->user()
        );

        return back()->with('success', "Adjustment {$product->name} berhasil disimpan.");
    }
}
