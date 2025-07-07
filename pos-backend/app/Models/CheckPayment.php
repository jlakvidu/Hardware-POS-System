<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CheckPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_id',
        'bank_name',
        'check_number',
        'check_date',
        'amount',
        'remarks',
    ];

    public function sale()
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }
}
