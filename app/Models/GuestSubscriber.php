<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class GuestSubscriber extends Model
{
    use Notifiable, HasPushSubscriptions;

    protected $fillable = ['session_id', 'last_active_at'];
}
