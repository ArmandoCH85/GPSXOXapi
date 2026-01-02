<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'message',
        'event_time',
        'lat',
        'lng',
        'speed',
        'altitude',
        'course',
        'address',
    ];

    protected $casts = [
        'event_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
