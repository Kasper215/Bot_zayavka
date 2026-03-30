<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;

use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasPushSubscriptions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'username',
        'password',
        "role",
        "birthday",
        "city",
        "email_verified_at",
        "blocked_at",
        "blocked_message",
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getRoleName(): string
    {
        $roles = [
            0 => 'Пользователь',
            1 => 'Менеджер',
            2 => 'Администратор',
            3 => 'Kasper',
        ];

        return $roles[$this->role] ?? 'Неизвестная роль';
    }

    /**
     * Check if user is admin or higher
     */
    public function isAdmin(): bool
    {
        return (int)$this->role === 2 || (int)$this->role === 3;
    }

    /**
     * Check if user is manager or higher
     */
    public function isManager(): bool
    {
        return (int)$this->role === 1 || (int)$this->role === 2 || (int)$this->role === 3;
    }

    /**
     * Check if user is Kasper
     */
    public function isKasper(): bool
    {
        return (int)$this->role === 3;
    }

}
