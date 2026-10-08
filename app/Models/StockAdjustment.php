<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $fillable = [
        'product_id',
        'scope',
        'qty',
        'stock_before',
        'stock_after',
        'reason',
        'adjust_date',
        'user_id',
    ];

    protected $casts = [
        'adjust_date' => 'date',
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
