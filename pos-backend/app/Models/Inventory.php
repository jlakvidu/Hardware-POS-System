<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantity',
        'restock_date_time',
        'added_stock_amount',
        'status'
    ];

    protected $casts = [
        'restock_date_time' => 'datetime',
        'quantity' => 'float', // Ensure precision for quantity
        'added_stock_amount' => 'float' // Ensure precision for added stock amount
    ];

    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'inventory_id');
    }

    // Add supplier relationship via product
    public function supplier()
    {
        return $this->hasOneThrough(
            \App\Models\Supplier::class,
            \App\Models\Product::class,
            'inventory_id', // Foreign key on products table
            'id',           // Foreign key on suppliers table
            'id',           // Local key on inventories table
            'supplier_id'   // Local key on products table
        );
    }
}
