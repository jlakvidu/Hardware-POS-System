<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalaryPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_id',
        'payment_period',
        'payment_date',
        'base_salary',
        'additions',
        'deductions',
        'net_pay',
        'payment_method',
        'notes',
        'invoice_number',
        'status'
    ];

    public function cashier()
    {
        return $this->belongsTo(Cashier::class);
    }
}
