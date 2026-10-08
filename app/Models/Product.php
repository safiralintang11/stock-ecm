<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'carton_qty',
        'unit',
        'min_stock',
        'status',
    ];

    public function mainStock()
    {
        return $this->hasOne(MainStock::class);
    }

    public function displayStocks()
    {
        return $this->hasMany(DisplayStock::class);
    }

    public function transfers()
    {
        return $this->hasMany(StockTransfer::class);
    }

    public function stockOuts()
    {
        return $this->hasMany(StockOut::class);
    }

    public function adjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function histories()
    {
        return $this->hasMany(StockHistory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Attach current balances as subquery columns to avoid N+1.
     */
    public function scopeWithBalances($query)
    {
        return $query->select('products.*')
            ->addSelect([
                'main_qty' => MainStock::select('qty')
                    ->whereColumn('product_id', 'products.id')
                    ->limit(1),
                'display_qty' => DisplayStock::select('closing_qty')
                    ->whereColumn('product_id', 'products.id')
                    ->orderByDesc('stock_date')
                    ->limit(1),
                'total_received' => StockHistory::selectRaw('COALESCE(SUM(qty),0)')
                    ->whereColumn('product_id', 'products.id')
                    ->whereIn('type', ['INITIAL_STOCK', 'STOCK_IN']),
            ]);
    }

    public function mainQty(): int
    {
        return (int) ($this->mainStock->qty ?? 0);
    }

    public function displayQty(): int
    {
        return (int) ($this->displayStocks()->orderByDesc('stock_date')->value('closing_qty') ?? 0);
    }

    public function stockStatus(): string
    {
        $display = $this->displayQty();

        if ($display <= 0) {
            return 'OUT_OF_STOCK';
        }

        return $display <= $this->min_stock ? 'LOW_STOCK' : 'OK';
    }
}
