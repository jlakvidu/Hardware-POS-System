<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product_Sales extends Model
{
    protected $table = 'product_sales';
    protected $fillable = [
        'sales_id',
        'product_id',
        'quantity',
        'price',
        'width_inch',
        'height_inch',
        'area_sqm',
        // ...other fields...
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }
}