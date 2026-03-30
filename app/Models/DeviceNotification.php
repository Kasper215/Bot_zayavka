<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceNotification extends Model
{
    protected $fillable = ['device_token_id', 'title', 'body', 'url', 'icon', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function deviceToken(): BelongsTo
    {
        return $this->belongsTo(DeviceToken::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
