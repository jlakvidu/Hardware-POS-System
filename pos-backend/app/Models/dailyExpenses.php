<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailyExpenses extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'custom_category',
        'description',
        'amount',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];
}
