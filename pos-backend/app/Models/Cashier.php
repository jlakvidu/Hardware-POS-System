<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cashier extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $table = 'cashier';
    protected $fillable = [
        'name',
        'email',
        'password',
        'image_path'
    ];

    protected $hidden = ['password'];
}
