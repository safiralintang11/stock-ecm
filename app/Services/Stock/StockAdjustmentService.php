<?php

namespace App\Services\Stock;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    public function __construct(protected StockEngine $engine)
    {
    }

    /**
     * Correct a stock balance (main or display) with a mandatory reason.
     * $delta is signed: positive = increase, negative = decrease.
     */
    public function execute(
        Product $product,
        string $scope,
        int $delta,
        string $reason,
        Carbon $date,
        User $user,
        bool $allowZero = false,
    ): StockAdjustment
    {
        if ($delta === 0 && ! $allowZero) {
            throw ValidationException::withMessages(['qty' => 'Qty penyesuaian tidak boleh 0.']);
        }

        if (trim((string) $reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan penyesuaian wajib diisi.']);
        }

        return DB::transaction(function () use ($product, $scope, $delta, $reason, $date, $user) {
            $mainBefore = $this->engine->mainBalance($product, lock: true);

            if ($scope === 'main') {
                $after = $mainBefore + $delta;

                if ($after < 0) {
                    throw ValidationException::withMessages(['qty' => 'Stock tidak boleh minus.']);
                }

                $main = $this->engine->setMain($product, $after);
                $displayBefore = $this->engine->displayBalance($product, lock: true);
                $display = ['before' => $displayBefore, 'after' => $displayBefore];
                $before = $mainBefore;
            } else {
                $day = $this->engine->ensureDisplayDay($product, $date);
                $displayBefore = (int) $day->closing_qty;
                $after = $displayBefore + $delta;

                if ($after < 0) {
                    throw ValidationException::withMessages(['qty' => 'Stock tidak boleh minus.']);
                }

                $day->adjust_qty += $delta;
                $day->closing_qty = $after;
                $day->save();

                $this->engine->recalcForward($product, $date->copy()->addDay());

                $main = ['before' => $mainBefore, 'after' => $mainBefore];
                $display = ['before' => $displayBefore, 'after' => $after];
                $before = $displayBefore;
            }

            $adjustment = StockAdjustment::create([
                'product_id' => $product->id,
                'scope' => $scope,
                'qty' => $delta,
                'stock_before' => $before,
                'stock_after' => $after,
                'reason' => $reason,
                'adjust_date' => $date->toDateString(),
                'user_id' => $user->id,
            ]);

            $this->engine->writeHistory(
                $product,
                'ADJUSTMENT',
                abs($delta),
                $main,
                $display,
                $date,
                $user,
                null,
                "Adjustment {$scope}: ".($delta > 0 ? '+' : '').$delta." ({$reason})"
            );

            $this->engine->writeAudit(
                'Stock Adjustment',
                'Adjustment',
                $product->sku,
                ['scope' => $scope, 'stock' => $before],
                ['scope' => $scope, 'stock' => $after],
                $reason,
                $user
            );

            return $adjustment;
        });
    }

    /**
     * Set the selected balance to an exact target, recording the difference as an adjustment.
     */
    public function setBalance(
        Product $product,
        string $scope,
        int $target,
        string $reason,
        Carbon $date,
        User $user,
    ): StockAdjustment {
        if ($target < 0) {
            throw ValidationException::withMessages(['qty' => 'Saldo akhir tidak boleh minus.']);
        }

        return DB::transaction(function () use ($product, $scope, $target, $reason, $date, $user) {
            $current = $scope === 'main'
                ? $this->engine->mainBalance($product, lock: true)
                : (int) $this->engine->ensureDisplayDay($product, $date, lock: true)->closing_qty;

            return $this->execute(
                $product,
                $scope,
                $target - $current,
                $reason,
                $date,
                $user,
                allowZero: true,
            );
        });
    }
}
