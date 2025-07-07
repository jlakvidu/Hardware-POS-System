<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sales extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'cashier_id',
        'amount',
        'payment_type',
        'status',
        'time',
        'advance_amount',
        'remaining_balance',
        'payment_status',
        'cart_discount',
        'product_discounts_total',
        'total_discount_amount',
        'check_details',
        'paid_amount',
        'balance_amount',
        'payment_info',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'payment_type' => 'string',
        'check_details' => 'json',
        'payment_info' => 'json',
    ];

    public function product_sales()
    {
        return $this->hasMany(Product_Sales::class, 'sales_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_sales', 'sales_id', 'product_id')
            ->withPivot('quantity', 'price');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function check_payment()
    {
        return $this->hasOne(CheckPayment::class, 'sales_id');
    }

    public static function allowedPaymentTypes()
    {
        return ['CASH', 'CARD', 'CHECK', 'ONLINE'];
    }
}
