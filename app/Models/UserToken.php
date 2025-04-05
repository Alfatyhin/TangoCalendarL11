<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserToken extends Model
{

    protected $fillable = [
        'userUid',
        'userRole',
        'token',
    ];

    use HasFactory;
}
