<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\DeviceNotification;
use App\Models\User;

class DeviceToken extends Model
{
    protected $fillable = ['token', 'user_id', 'user_agent', 'last_seen_at'];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(DeviceNotification::class);
    }

    public function pendingNotifications(): HasMany
    {
        return $this->hasMany(DeviceNotification::class)->whereNull('read_at')->orderBy('created_at');
    }
}
