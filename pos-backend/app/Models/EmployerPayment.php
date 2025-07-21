<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployerPayment extends Model
{
    use HasFactory;

    protected $table = 'employer_payments';

    protected $fillable = [
        'cashier_id',
        'salary_amount',
        'payment_duration',
        'payment_date',
        'payment_method',
        'notes'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'salary_amount' => 'decimal:2'
    ];

    // Define the relationship with explicit column names
    public function cashier()
    {
        return $this->belongsTo(Cashier::class, 'cashier_id', 'id');
    }
}
