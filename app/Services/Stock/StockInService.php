<?php

namespace App\Services\Stock;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockInService
{
    public function __construct(protected StockEngine $engine)
    {
    }

    /**
     * Record incoming warehouse stock (STOCK UTAMA) in PCS.
     * First entry for a product is INITIAL_STOCK, later ones are STOCK_IN.
     */
    public function execute(Product $product, int $qty, Carbon $date, User $user, ?string $note = null): void
    {
        if ($qty <= 0) {
            throw ValidationException::withMessages(['qty' => 'Qty harus lebih besar dari 0.']);
        }

        DB::transaction(function () use ($product, $qty, $date, $user, $note) {
            $mainBefore = $this->engine->mainBalance($product, lock: true);
            $displayBefore = $this->engine->displayBalance($product, lock: true);

            $main = $this->engine->addMain($product, $qty);
            $display = ['before' => $displayBefore, 'after' => $displayBefore];

            $type = $mainBefore === 0 ? 'INITIAL_STOCK' : 'STOCK_IN';

            $this->engine->writeHistory($product, $type, $qty, $main, $display, $date, $user, null, $note);
        });
    }
}
