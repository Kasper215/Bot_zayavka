<?php

namespace App\Classes;

use App\Enums\RoleEnum;
use App\Models\Agent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPUnit\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;


class BotManager extends BotCore
{
    use BotMethodsTrait;


    public function bot()
    {
        $this->controller = null;
        return $this;
    }

    public function currentBotUser()
    {
        return $this->botUser ?? null;
    }

    protected function createUser($from)
    {
        $telegram_chat_id = $from->id; //идентификатор чата пользователя из телеграм
        $first_name = $from->first_name ?? null; //имя пользователя из телеграм
        $last_name = $from->last_name ?? null; //фамилия пользователя из телеграм
        $username = $from->username ?? null; //псевдоним пользователя
        $fullName = trim(($first_name ?? '') . ' ' . ($last_name ?? ''));
        $displayName = !empty($fullName) ? $fullName : ($username ?? (string)$telegram_chat_id);

        Log::info("Telegram User Data: id=$telegram_chat_id, first_name=$first_name, last_name=$last_name, username=$username, resolved_name=$displayName");

        $this->botUser = User::query()
            ->where("telegram_chat_id", $telegram_chat_id)
            ->first();

        if (is_null($this->botUser)) {
            $this->botUser = User::query()->create([
                'email' => "$telegram_chat_id@" . env('APP_EMAIL_DOMAIN'),
                'name' => $displayName,
                'username' => $username,
                'password' => bcrypt(Str::random(32)),
                'role' => RoleEnum::USER->value,
                'telegram_chat_id' => $telegram_chat_id,
                'fio_from_telegram' => $fullName ?: null,
            ]);


            $tmpUserLink = "\n<a href='tg://user?id=" . $this->botUser->telegram_chat_id . "'>Перейти к чату с пользователем</a>";

            $this->sendMessage(
                env("TELEGRAM_ADMIN_CHANNEL"),
                "Создан новый пользователь <b>$displayName</b> (@$username)\n$tmpUserLink"
            );

        } else {
            $this->botUser->name = $displayName;
            $this->botUser->fio_from_telegram = $fullName ?: null;
            $this->botUser->username = $username;
            $this->botUser->updated_at = Carbon::now();
            $this->botUser->save();
        }
    }

    protected function checkIsUserBlocked(): bool
    {

        if (!is_null($this->botUser->blocked_at ?? null)) {
            $this->reply($this->botUser->blocked_message ??
                "Мы заметили у вас подозрительную активность и временно ограничили доступ. Хорошего дня!");
            return true;
        }
        return false;
    }

    protected function botStatusHandler(): BotStatusEnum
    {
        $isWorking = env("TELEGRAM_BOT_IS_WORKING");

        if ($isWorking ?? false)
            return BotStatusEnum::Working;

        $message = 'Техническое обслуживание';

        $this
            ->replyPhoto("\xF0\x9F\x9A\xA8В данный момент сервис временно недосутепн! Обратитесь в тех. поддержку:\xF0\x9F\x9A\xA8\n\n<em><b>$message</b></em>",
                InputFile::create(public_path() . "/images/maintenance.png")
            );

        return BotStatusEnum::InMaintenance;
    }

    public function getRoutes()
    {
        return $this->routes;
    }


    public function setWebhook()
    {
        $botUrl = env("TELEGRAM_WEBHOOK_URL") ?? env("APP_URL") . "/webhook";
        $token = env("TELEGRAM_BOT_TOKEN");
        $telegramUrl = "https://api.telegram.org/bot$token/setWebhook?url=$botUrl";
        $response = Http::get($telegramUrl);
        return $response->json();
    }

    public function setApiToken()
    {
        try {
            $token = env("TELEGRAM_BOT_TOKEN");
            $this->bot = new Api($token);
        } catch (\Exception $e) {
            $this->bot = null;
        }
        return $this;
    }

}
