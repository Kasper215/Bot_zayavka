<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use App\Facades\BotMethods;
use App\Http\Middleware\Service\Utilities;
use App\Models\Bot;
use App\Models\BotUser;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TelegramAdminCheck
{
    use Utilities;

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $isDebug = env("APP_DEBUG");

        if ($isDebug){
            $botUser = User::query()
                ->first();

            $botUser->telegram_chat_id = env("TELEGRAM_ADMIN_CHANNEL");
            $botUser->fio_from_telegram = "РЕЖИМ ОТЛАДКИ СИСТЕМЫ";
            $botUser->role = RoleEnum::SUPERADMIN->value;

            $request->botUser = $botUser;

            return $next($request);
        }

        Log::info("TelegramAdminCheck");
        $headerTgDataEncrypted = $request->header("X-Tg-Data") ?? null;
        $tgData = $request->tgData;

        Log::info("TelegramAdminCheck tgData".print_r($tgData, true));

        if (!is_null($headerTgDataEncrypted))
            $tgData = base64_decode($headerTgDataEncrypted);

        parse_str($tgData, $arr);

        $tgUser = $arr['user'] ?? null;

        if (is_null($tgUser))
            return \response()->json(["error" => "TG user not found"], 404);

        $tgUser = json_decode($tgUser);

        $botUser = User::query()
            ->where("telegram_chat_id", $tgUser->id)
            ->first();

        if (is_null($botUser))
            return \response()->json(["error" => "Bot User not found"], 400);

        if (!$botUser->is_admin) {
            return \response()->json(["error" => "User is not admin"], 400);
        }


        if ($this->validateTGData($tgData)) {
            $request->botUser = $botUser;
            return $next($request);
        } else {
            return \response()->json(["error" => "some error"], 400);
        }


    }
}
