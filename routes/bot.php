<?php

use App\Facades\BotManager;
use App\Http\Controllers\Bots\InlineBotController;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Telegram\Bot\FileUpload\InputFile;

BotManager::bot()
    ->controller(\App\Http\Controllers\TelegramController::class)
    ->route("/.*Мой id|.*мой id", "getMyId")
    ->route("/start", "startCommand")
    ->route("✍️ Оставить заявку", "orderCommand")
    ->route("📚 Наши услуги и цены", "servicesCommand")
    ->route(".*услуги и цены.*", "servicesCommand")
    ->route("📂 Портфолио / Примеры", "portfolioCommand")
    ->route(".*Портфолио.*", "portfolioCommand")
    ->route("📞 Контакты", "contactsCommand")
    ->route(".*Контакты.*", "contactsCommand")
    ->route("/run_miniapp", "runMiniApp")
    ->route("/apologize", "runApologize")
    ->route("/about", "aboutCommand")
    ->route("/help", "helpCommand")
    ->route("/adminmenu", "adminMenuCommand")
    ->route("/start ([0-9a-zA-Z=]+)", "startWithParam")
    ->fallback("handleWizard")
    ->fallbackAudio("handleWizard")
    ->fallbackDocument("handleWizard")
    ->fallbackVideo("handleWizard")
    ->fallbackSticker("handleWizard")
    ->fallbackPhoto("handleWizard");


/*BotManager::bot()
    ->fallbackVideo(function (...$data) {

    })
    ->fallbackDocument(function (...$data) {

    })
    ->fallbackPhoto(function (...$data) {

    });*/

BotManager::bot()
    ->location(function (...$data) {

    });
