<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrower_name',
        'amount',
        'loan_date',
        'due_date',
        'status',
        'description',
    ];
}
