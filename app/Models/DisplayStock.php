<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisplayStock extends Model
{
    protected $fillable = [
        'product_id',
        'stock_date',
        'opening_qty',
        'in_qty',
        'out_qty',
        'closing_qty',
    ];

    protected $casts = [
        'stock_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
