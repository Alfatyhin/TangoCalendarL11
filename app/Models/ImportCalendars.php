<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportCalendars extends Model
{
    protected $casts = [
        'settings' => 'array',
    ];
}
