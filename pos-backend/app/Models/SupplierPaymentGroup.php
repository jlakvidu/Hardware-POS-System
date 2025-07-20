<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPaymentGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'product_id',
        'inventory_id',
        'admin_id',
        'total_cost',
        'remaining_balance',
        'payment_status', // 'advance' or 'full'
        'notes'
    ];

    public function transactions()
    {
        return $this->hasMany(SupplierPaymentTransaction::class, 'group_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
