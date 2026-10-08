<?php

namespace App\Services\Stock;

use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function __construct(protected StockEngine $engine)
    {
    }

    /**
     * Move stock from STOCK UTAMA to STOCK DISPLAY.
     * Atomic: main decreases, display day in_qty increases, both histories written.
     */
    public function execute(Product $product, int $qty, Carbon $date, User $user, ?string $note = null): StockTransfer
    {
        if ($qty <= 0) {
            throw ValidationException::withMessages(['qty' => 'Qty harus lebih besar dari 0.']);
        }

        return DB::transaction(function () use ($product, $qty, $date, $user, $note) {
            $mainBefore = $this->engine->mainBalance($product, lock: true);

            if ($qty > $mainBefore) {
                throw ValidationException::withMessages([
                    'qty' => "Stock Utama tidak cukup. Tersedia {$mainBefore} {$product->unit}.",
                ]);
            }

            $day = $this->engine->ensureDisplayDay($product, $date);
            $displayBefore = (int) $day->closing_qty;

            // Main stock decreases
            $main = $this->engine->setMain($product, $mainBefore - $qty);

            // Display day increases
            $day->in_qty += $qty;
            $day->closing_qty += $qty;
            $day->save();

            $this->engine->recalcForward($product, $date->copy()->addDay());

            $display = ['before' => $displayBefore, 'after' => $day->closing_qty];

            $trxNo = $this->engine->nextTrxNo('ST', 'stock_transfers', 'transfer_date', $date);

            $transfer = StockTransfer::create([
                'trx_no' => $trxNo,
                'product_id' => $product->id,
                'qty' => $qty,
                'from_location' => 'Stock Utama',
                'to_location' => 'Stock Display',
                'transfer_date' => $date->toDateString(),
                'user_id' => $user->id,
                'note' => $note,
            ]);

            $this->engine->writeHistory(
                $product,
                'STOCK_TRANSFER',
                $qty,
                $main,
                $display,
                $date,
                $user,
                $trxNo,
                $note ?: "Transfer {$qty} {$product->unit} dari Stock Utama ke Stock Display"
            );

            return $transfer;
        });
    }
}
