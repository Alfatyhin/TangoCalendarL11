<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FcmToken extends Model
{
    protected $fillable = [
        'user_uid',
        'fcm_tokens',
    ];
    protected $casts = [
        'fcm_tokens' => 'array',
    ];

    public function subscribes(): HasMany
    {
        return $this->hasMany(
            MessagesSubscribes::class,
            'user_uid',
            'user_uid'
        );
    }
}
