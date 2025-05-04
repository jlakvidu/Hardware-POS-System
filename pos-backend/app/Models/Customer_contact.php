<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer_contact extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $table = 'customer_contact';
    protected $fillable = [
        'contact_number',
        'customer_id'
    ];
}
