<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $last_webhook_at
 */
class CalendarWebhookIds extends Model
{
    use HasFactory;

    protected $casts = [
        'data' => 'array',
        'expires_at' => 'datetime',
        'last_webhook_at' => 'datetime',
    ];
    public function calendar()
    {
        return $this->belongsTo(
            Gcalendar::class,
            'calendarId',
            'id'
        );
    }
}
