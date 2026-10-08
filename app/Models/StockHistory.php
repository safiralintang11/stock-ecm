<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    public const TYPES = [
        'INITIAL_STOCK',
        'STOCK_IN',
        'STOCK_TRANSFER',
        'STOCK_DISPLAY',
        'OUT',
        'ADJUSTMENT',
    ];

    protected $fillable = [
        'product_id',
        'type',
        'qty',
        'main_before',
        'main_after',
        'display_before',
        'display_after',
        'trx_date',
        'user_id',
        'reference',
        'description',
    ];

    protected $casts = [
        'trx_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
