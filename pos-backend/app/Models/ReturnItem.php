<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnItem extends Model
{
    use HasFactory;

    protected $table = 'return_item';

    protected $fillable = [
        'reason',
        'quantity',
        'product_id',
        'created_at',
        'updated_at'
    ];

    public function salesReturnItem()
    {
        return $this->hasOne(SalesReturnItem::class, 'return_item_id');
    }
}
