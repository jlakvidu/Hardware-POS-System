<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'product_id',
        'inventory_id',
        'admin_id',
        'amount',
        'payment_method', // 'cash' or 'check'
        'check_number',   // nullable, only for check payments
        'paid_at',
        'notes'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'float',
    ];

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function inventory()
    {
        return $this->belongsTo(\App\Models\Inventory::class);
    }

    public function admin()
    {
        return $this->belongsTo(\App\Models\Admin::class);
    }
}
