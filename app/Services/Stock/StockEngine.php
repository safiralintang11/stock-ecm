<?php

namespace App\Services\Stock;

use App\Models\AuditLog;
use App\Models\DisplayStock;
use App\Models\MainStock;
use App\Models\Product;
use App\Models\StockHistory;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockEngine
{
    /**
     * Current main (warehouse) balance for a product.
     */
    public function mainBalance(Product $product, bool $lock = false): int
    {
        $query = MainStock::where('product_id', $product->id);

        if ($lock) {
            $query->lockForUpdate();
        }

        return (int) ($query->value('qty') ?? 0);
    }

    /**
     * Current display balance for a product (closing of the latest daily row).
     */
    public function displayBalance(Product $product, bool $lock = false): int
    {
        $query = DisplayStock::where('product_id', $product->id);

        if ($lock) {
            $query->lockForUpdate();
        }

        return (int) ($query->orderByDesc('stock_date')->value('closing_qty') ?? 0);
    }

    /**
     * Display balance as of a given date: closing of that day's row,
     * or the latest closing before that date (carried forward), else 0.
     */
    public function displayBalanceAt(Product $product, Carbon $date): int
    {
        $sameDay = DisplayStock::where('product_id', $product->id)
            ->whereDate('stock_date', $date->toDateString())
            ->value('closing_qty');

        if ($sameDay !== null) {
            return (int) $sameDay;
        }

        return (int) (DisplayStock::where('product_id', $product->id)
            ->whereDate('stock_date', '<', $date->toDateString())
            ->orderByDesc('stock_date')
            ->value('closing_qty') ?? 0);
    }

    /**
     * Find or create the daily display row for a product on a date.
     * Opening = closing balance of the previous day (daily stock balance).
     */
    public function ensureDisplayDay(Product $product, Carbon $date, bool $lock = true): DisplayStock
    {
        $query = DisplayStock::where('product_id', $product->id)
            ->whereDate('stock_date', $date->toDateString());

        if ($lock) {
            $query->lockForUpdate();
        }

        $row = $query->first();

        if ($row) {
            return $row;
        }

        $prevClosing = (int) (DisplayStock::where('product_id', $product->id)
            ->whereDate('stock_date', '<', $date->toDateString())
            ->orderByDesc('stock_date')
            ->lockForUpdate()
            ->value('closing_qty') ?? 0);

        return DisplayStock::create([
            'product_id' => $product->id,
            'stock_date' => $date->toDateString(),
            'opening_qty' => $prevClosing,
            'in_qty' => 0,
            'out_qty' => 0,
            'adjust_qty' => 0,
            'closing_qty' => $prevClosing,
        ]);
    }

    /**
     * Recalculate closing for a day and propagate openings forward
     * so backdated mutations keep every later day consistent.
     */
    public function recalcForward(Product $product, Carbon $fromDate): void
    {
        $rows = DisplayStock::where('product_id', $product->id)
            ->whereDate('stock_date', '>=', $fromDate->toDateString())
            ->orderBy('stock_date')
            ->lockForUpdate()
            ->get();

        $prevClosing = (int) (DisplayStock::where('product_id', $product->id)
            ->whereDate('stock_date', '<', $fromDate->toDateString())
            ->orderByDesc('stock_date')
            ->value('closing_qty') ?? 0);

        foreach ($rows as $row) {
            $row->opening_qty = $prevClosing;
            $row->closing_qty = max(0, $row->opening_qty + $row->in_qty - $row->out_qty + $row->adjust_qty);
            $row->save();

            $prevClosing = $row->closing_qty;
        }
    }

    /**
     * Add qty to the main stock balance (creates the row on first input).
     */
    public function addMain(Product $product, int $qty): array
    {
        $before = $this->mainBalance($product, lock: true);

        $main = MainStock::where('product_id', $product->id)->lockForUpdate()->first();

        if ($main) {
            $main->qty = $before + $qty;
            $main->save();
        } else {
            $main = MainStock::create([
                'product_id' => $product->id,
                'qty' => $qty,
            ]);
        }

        return ['before' => $before, 'after' => $before + $qty];
    }

    public function setMain(Product $product, int $newQty): array
    {
        $before = $this->mainBalance($product, lock: true);

        $main = MainStock::where('product_id', $product->id)->lockForUpdate()->first();

        if ($main) {
            $main->qty = $newQty;
            $main->save();
        } else {
            $main = MainStock::create([
                'product_id' => $product->id,
                'qty' => $newQty,
            ]);
        }

        return ['before' => $before, 'after' => $newQty];
    }

    /**
     * Persist a ledger entry capturing both balances before/after.
     */
    public function writeHistory(
        Product $product,
        string $type,
        int $qty,
        array $main,
        array $display,
        Carbon $date,
        User $user,
        ?string $reference = null,
        ?string $description = null,
    ): StockHistory {
        return StockHistory::create([
            'product_id' => $product->id,
            'type' => $type,
            'qty' => $qty,
            'main_before' => $main['before'],
            'main_after' => $main['after'],
            'display_before' => $display['before'],
            'display_after' => $display['after'],
            'trx_date' => $date->toDateString(),
            'user_id' => $user->id,
            'reference' => $reference,
            'description' => $description,
        ]);
    }

    public function writeAudit(
        string $action,
        string $module,
        ?string $sku = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?User $user = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => ($user ?? Auth::user())?->id,
            'action' => $action,
            'module' => $module,
            'sku' => $sku,
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * Generate a sequential document number: PREFIX-YYYYMMDD-001.
     */
    public function nextTrxNo(string $prefix, string $table, string $dateColumn, Carbon $date): string
    {
        $like = $prefix.'-'.$date->format('Ymd').'-%';

        $last = DB::table($table)
            ->where($dateColumn, $date->toDateString())
            ->where('trx_no', 'like', $like)
            ->lockForUpdate()
            ->orderByDesc('trx_no')
            ->value('trx_no');

        $seq = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $prefix.'-'.$date->format('Ymd').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
