<?php

namespace App\Services\Stock;

use App\Models\Product;
use App\Models\StockOut;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOutService
{
    public function __construct(protected StockEngine $engine)
    {
    }

    /**
     * Record an OUT transaction: stock leaves the DISPLAY, not the main stock.
     * Atomic: display day out_qty increases, closing decreases, history written.
     */
    public function execute(Product $product, int $qty, Carbon $date, User $user, ?string $note = null): StockOut
    {
        if ($qty <= 0) {
            throw ValidationException::withMessages(['qty' => 'Qty harus lebih besar dari 0.']);
        }

        return DB::transaction(function () use ($product, $qty, $date, $user, $note) {
            $day = $this->engine->ensureDisplayDay($product, $date);
            $displayBefore = (int) $day->closing_qty;

            if ($qty > $displayBefore) {
                throw ValidationException::withMessages([
                    'qty' => "OUT tidak boleh melebihi Stock Display. Tersedia {$displayBefore} {$product->unit}.",
                ]);
            }

            $mainBefore = $this->engine->mainBalance($product, lock: true);

            $day->out_qty += $qty;
            $day->closing_qty -= $qty;
            $day->save();

            $this->engine->recalcForward($product, $date->copy()->addDay());

            $main = ['before' => $mainBefore, 'after' => $mainBefore];
            $display = ['before' => $displayBefore, 'after' => $day->closing_qty];

            $trxNo = $this->engine->nextTrxNo('OUT', 'stock_outs', 'out_date', $date);

            $out = StockOut::create([
                'trx_no' => $trxNo,
                'product_id' => $product->id,
                'qty' => $qty,
                'out_date' => $date->toDateString(),
                'user_id' => $user->id,
                'note' => $note,
            ]);

            $this->engine->writeHistory($product, 'OUT', $qty, $main, $display, $date, $user, $trxNo, $note);

            return $out;
        });
    }
}
