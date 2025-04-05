<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessagesSubscribes extends Model
{

    protected $fillable = [
        'user_uid',
        'event_subscribe',
    ];
    protected $casts = [
        'data_subscribe' => 'array',
    ];

}
