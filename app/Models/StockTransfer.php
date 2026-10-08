<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'trx_no',
        'product_id',
        'qty',
        'from_location',
        'to_location',
        'transfer_date',
        'user_id',
        'note',
    ];

    protected $casts = [
        'transfer_date' => 'date',
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
