<?php

namespace App\Services\Stock;

use App\Models\Product;
use App\Models\StockOut;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SisaDisplayService
{
    public function __construct(
        protected StockEngine $engine,
        protected StockOutService $outService,
    ) {
    }

    /**
     * Input manual sisa stock display (hasil hitung fisik).
     * Selisih antara stock display sistem dan sisa fisik dicatat sebagai OUT:
     * OUT = Stock Display - Sisa Display (input).
     * Return null ketika tidak ada selisih (tidak ada OUT dicatat).
     */
    public function execute(Product $product, int $sisaQty, Carbon $date, User $user, ?string $note = null): ?StockOut
    {
        $display = $this->engine->displayBalance($product);

        if ($sisaQty > $display) {
            throw ValidationException::withMessages([
                'qty' => "Sisa display tidak boleh lebih besar dari Stock Display ({$display} {$product->unit}).",
            ]);
        }

        $outQty = $display - $sisaQty;

        if ($outQty === 0) {
            return null;
        }

        return $this->outService->execute(
            $product,
            $outQty,
            $date,
            $user,
            $note ?: 'Sisa display diinput manual'
        );
    }
}
