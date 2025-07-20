<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'amount_paid',
        'payment_method', // 'cash' or 'check'
        'check_number',
        'bank_name',
        'notes',
        'paid_at'
    ];

    public function group()
    {
        return $this->belongsTo(SupplierPaymentGroup::class, 'group_id');
    }
}
