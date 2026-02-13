<?php

namespace App\Classes;

use App\Models\Bot;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class BotMethods
{
    use BotBaseMethodsTrait;

    private $bot;

    public function bot()
    {
        $token = env("TELEGRAM_BOT_TOKEN");

        $this->bot = new Api($token);

        return $this;
    }

}
