<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

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
        "fio_from_telegram",
        "telegram_chat_id",
        "role",
        "birthday",
        "city",
        "email_verified_at",
        "password",
        "blocked_at",
        "city",
        "blocked_message",
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',

    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getUserTelegramLink(): string
    {
        return "\n<a href='tg://user?id=" . $this->telegram_chat_id . "'>Перейти к чату с пользователем</a>";
    }

    public function getRoleName(): string
    {
        $roles = [
            0 => 'Пользователь',
            1 => 'Администратор',
            2 => 'Менеджер',
        ];

        return $roles[$this->role] ?? 'Неизвестная роль';
    }

    public function toTelegramText(): string
    {
        if (!is_null($this->birthday ?? null))
            $age = Carbon::parse($this->birthday)->age;

        $fields = [
            '#' => $this->id,
            'ФИО' => $this->name,
            'ФИО из Telegram' => $this->fio_from_telegram,
            'День рождения' => $this->birthday ?? 'не заполнен',
            'Возраст' => $age ?? 'не заполнен',
            'Регион' => $this->region ?? 'не заполнен',
            'Город' => $this->city ?? 'не заполнен',
            'ID чата Telegram' => $this->telegram_chat_id,
            'Username' => $this->username,
            'Создан' => $this->created_at,
            'Обновлён' => $this->updated_at,
        ];

        $text = "";
        foreach ($fields as $label => $value) {
            if (!empty($value)) {
                $text .= "{$label}: {$value}\n";
            }
        }

        return trim($text);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return (int)$this->role === 1;
    }

    /**
     * Check if user is manager
     */
    public function isManager(): bool
    {
        return (int)$this->role === 2;
    }

}
