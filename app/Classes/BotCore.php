<?php

namespace App\Classes;


use App\Facades\BotManager;
use App\Facades\BotMethods;

use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

use PHPUnit\Exception;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telegram\Bot\FileUpload\InputFile;

abstract class BotCore
{
    protected $bot;

    protected $controller = null;

    protected $chatId;

    protected $inline = null;

    protected $routes = [];

    protected $next = [];

    protected $botUser;
    protected $slugs = [];
    protected $currentUpdateId = null;

    protected abstract function currentBotUser();

    protected abstract function createUser($data);

    protected abstract function setWebhook();


    protected abstract function botStatusHandler(): int;

    protected abstract function checkIsUserBlocked(): bool;

    public abstract function setApiToken();

    public abstract function reply($message, $messageThreadId = null);

    public abstract function replyAction($action = "typing", $messageThreadId = null);


    public function getCurrentChatId()
    {
        return $this->chatId;
    }

    public function tryCall($item, $message, $config = null, ...$arguments)
    {

        //$config = is_null($config) ? null : json_decode($config);

        $find = false;
        try {
            if (is_callable($item["function"])) {
                app()->call($item["function"], [$message, $config, ... $arguments]);
            } else {
                app()->call((!is_null($item["controller"]) ?
                    $item["controller"] . "@" . $item["function"] :
                    $item["function"]),
                    [$message, $config, ... $arguments]);
            }


            $find = true;
        } catch (\Exception $e) {
            Log::info($e->getMessage() . " " . $e->getFile() . " " . $e->getLine());
        }

        return $find;
    }

    public function inlineHandler($data)
    {
        if (is_null($this->inline))
            return;


        $inlineData = $data["inline_query"];

        $this->chatId = $data["from"]["id"] ?? null;

        /*        $id = $data["inline_query"]["id"] ?? null;
                $query = $data["inline_query"]["query"] ?? null;
                $offset = $data["inline_query"]["offset"] ?? null;

                $this->chatId = $data["inline_query"]["from"]["id"] ?? null;*/

        /*    InlineQueryService::inline()
                ->setBot($this->getSelf())
                ->handler($inlineData);*/

        //  $this->tryCall($this->inline, $query, null, $id, $offset);
    }

    private function botLocationHandler($coords, $message): bool
    {
        if (is_null($coords))
            return false;

        foreach ($this->routes as $item) {

            if (is_null($item["path"]))
                continue;

            if ($item["path"] === "location")
                try {
                    $item["function"]($message, (object)[
                        "lat" => $coords->latitude,
                        "lon" => $coords->longitude
                    ]);
                    return true;
                } catch (\Exception $e) {
                    Log::info($e->getMessage() . " " . $e->getFile() . " " . $e->getLine());
                }
        }

        return false;
    }

    private function botRouteHandler($message, $query): bool
    {
        $find = false;
        $matches = [];
        $arguments = [];

        foreach ($this->routes as $item) {

            if (is_null($item["path"]) || $item["is_service"])
                continue;

            $reg = $item["path"];

            if (str_starts_with($reg, "/"))
                $reg = substr($reg, 1);

            if (preg_match("~" . $reg . "$~iu", $query, $matches)) {
                foreach ($matches as $match)
                    $arguments[] = $match;

                $find = $this->tryCall($item, $message, null, ...$arguments);
                break;
            }

        }

        return $find;
    }

    private function botNextHandler($message): bool
    {
        $find = false;
        if (!empty($this->next)) {
            foreach ($this->next as $item) {
                $find = $this->tryCall($item, $message);
            }
        }

        return $find;
    }

    private function botFallbackHandler($message): bool
    {
        $find = false;
        foreach ($this->routes as $item) {

            if (is_null($item["path"]))
                continue;

            if ($item["path"] === "fallback") {
                $find = $this->tryCall($item, $message);
            }
        }
        return $find;
    }

    private function botFallbackVideoHandler($message): bool
    {
        $video = $message->video ?? $message->video_note ?? null;
        $caption = $message->caption ?? null;

        $type = isset($message->video) ? "video" : "video_note";


        if (is_null($video))
            return false;

        $find = false;
        foreach ($this->routes as $item) {

            if (is_null($item["path"]))
                continue;

            if ($item["path"] === "fallback_video") {
                $find = $this->tryCall($item, $message, null, ($caption ?? null), $video, $type);
            }
        }
        return $find;
    }

    private function botFallbackAudioHandler($message): bool
    {
        $audio = $message->audio ?? $message->voice ?? null;
        $caption = $message->caption ?? $message->audio->title ?? $message->audio->file_name ?? null;

        $type = !is_null($message->audio ?? null) ? "audio" : "voice";


        if (is_null($audio))
            return false;

        $find = false;
        foreach ($this->routes as $item) {

            if (is_null($item["path"]))
                continue;

            if ($item["path"] === "fallback_audio") {
                $find = $this->tryCall($item, $message, null, ($caption ?? null), $audio, $type);
            }
        }
        return $find;
    }

    private function botFallbackStickerHandler($message): bool
    {
        $sticker = $message->sticker ?? null;
        $caption = $message->caption ?? null;

        $type = "sticker";


        if (is_null($sticker))
            return false;

        $find = false;
        foreach ($this->routes as $item) {

            if (is_null($item["path"]))
                continue;

            if ($item["path"] === "fallback_sticker") {
                $find = $this->tryCall($item, $message, null, ($caption ?? null), $sticker, $type);
            }
        }
        return $find;
    }

    private function botFallbackDocumentHandler($message): bool
    {
        $document = $message->document ?? $message->animation ?? null;
        $caption = $message->caption ?? $message->document->file_name ?? $message->animation->file_name ?? null;

        $type = "document";


        if (is_null($document))
            return false;

        $find = false;
        foreach ($this->routes as $item) {

            if (is_null($item["path"]))
                continue;

            if ($item["path"] === "fallback_document") {
                $find = $this->tryCall($item, $message, null, ($caption ?? null), $document, $type);
            }
        }
        return $find;
    }

    private function botFallbackPhotoHandler($message): bool
    {
        $photos = $message->photo ?? null;
        $caption = $message->caption ?? null;

        if (is_null($photos))
            return false;

        $find = false;
        foreach ($this->routes as $item) {

            if (is_null($item["path"]))
                continue;

            if ($item["path"] === "fallback_photo") {
                $find = $this->tryCall($item, $message, null, ($caption ?? null), [...$photos]);
            }
        }
        return $find;
    }


    public function successfulPaymentHandler($data)
    {
        $totalAmount = $data->total_amount;
        $currency = $data->currency;
        $payload = $data->invoice_payload;
        $orderInfo = $data->order_info ?? null;
        $telegramPaymentChargeId = $data->telegram_payment_charge_id;
        $providerPaymentChargeId = $data->provider_payment_charge_id;


    }

    public function shippingQueryHandler($data)
    {

        $answerShippingQuery = $data->id;
        $telegramChatId = $data->from->id;
        $payload = $data->invoice_payload;
        $shippingAddress = $data->shipping_address;

    }


    public function handler()
    {
        $this->setApiToken();

        if (is_null($this->bot))
            return;

        $update = $this->bot->getWebhookUpdate();
        $updateId = $update['update_id'] ?? null;
        $this->currentUpdateId = $updateId;

        // Защита от повторных запросов Telegram (retries)
        if ($updateId) {
            $lockKey = "tg_update_processed_{$updateId}";
            if (Cache::has($lockKey)) {
                return;
            }
            Cache::put($lockKey, true, now()->addMinutes(5));
        }

        //Log::info(print_r($update, true));

        include_once base_path('routes/bot.php');

        $item = json_decode($update);

        if (isset($update["channel_post"])) {

            $text = $item->channel_post->text ?? null;

            if (mb_strtolower($text) === "мой id")
                BotMethods::bot()
                    ->sendMessage($item->channel_post->sender_chat->id,
                        "Ваш id=>" . $item->channel_post->sender_chat->id
                    );
            return;
        }

        if (isset($update["inline_query"])) {
            $this->createUser($item->inline_query->from);
            $this->inlineHandler($update);
            
            return;
        }
        try {
            if (isset($update["pre_checkout_query"])) {
                // $this->preCheckoutQueryHandler($item->pre_checkout_query);
                return;
            }

            if (isset($update["shipping_query"])) {
                //  $this->shippingQueryHandler($item->shipping_query);
                return;
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . " " . $e->getFile() . " " . $e->getLine());
        }


        //формируем сообщение из возможных вариантов входных данных
        $message = $item->message ??
            $item->edited_message ??
            $item->callback_query->message ??

            null;

        //если сообщения нет, то завершаем работу
        if (is_null($message))
            return;


        //разделяем логику получения данных об отправителе,
        // так как данные приходят в разных частях JSON-объекта,
        // то создадим условие, по которому будем различать откуда получать эти данные

        if (isset($update["callback_query"]))
            $this->createUser($item->callback_query->from);
        else
            $this->createUser($message->from);

        if ($this->checkIsUserBlocked())
            return;

        try {
            if (isset($update["message"]["successful_payment"])) {
                $this->successfulPaymentHandler($item->message->successful_payment);
                return;
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage() . " " . $e->getFile() . " " . $e->getLine());
        }


        try {


            $query = $item->message->text ??
                $item->callback_query->data ??
                $item->edited_message->text ??
                $item->message->contact->phone_number ?? '';

            if (!is_null($item->message->contact ?? null)) {
                $botUser = $this->currentBotUser();
                $botUser->phone = $item->message->contact->phone_number ?? $botUser->phone ?? null;
                $botUser->save();

            }


            $this->chatId = $message->chat->id;

            $botStatus = $this->botStatusHandler();

            if ($botStatus != BotStatusEnum::Working)
                return;


            $coords = !isset($update["message"]["location"]) ? null :
                (object)[
                    "latitude" => $update["message"]["location"]["latitude"] ?? 0,
                    "longitude" => $update["message"]["location"]["longitude"] ?? 0
                ];

            if ($this->botLocationHandler($coords, $message))
                return;


            if ($this->botRouteHandler($message, $query))
                return;

            if ($this->botNextHandler($message))
                return;

            if ($this->botFallbackStickerHandler($message))
                return;

            if ($this->botFallbackPhotoHandler($message))
                return;

            if ($this->botFallbackVideoHandler($message))
                return;

            if ($this->botFallbackAudioHandler($message))
                return;

            if ($this->botFallbackDocumentHandler($message))
                return;

            if ($this->botFallbackHandler($message))
                return;

            if ($this->adminNotificationHandler($message, $query))
                return;

            if (($update["message"]["chat"]["is_forum"] ?? 0) == 0) {
                // Проверяем, было ли отправлено хоть одно сообщение в рамках этого запроса
                $messageSent = Cache::get("tg_message_sent_{$updateId}", false);
                if (!$messageSent) {
                    $this->reply("Ошибка обработки данных!");
                }
            }
        } catch (Exception $e) {
            Log::info("in handler function=>" . $e->getMessage() . " " . $e->getFile() . " " . $e->getLine());

        }
    }


    public function pushCommand(string $command): void
    {

        if (is_null($this->bot))
            throw new HttpException(404, "Бот не найден!");

        if (is_null($command))
            return;

        include_once base_path('routes/bot.php');


        if ($this->botRouteHandler(null, $command))
            return;

        $this->reply("Ошибка обработки данных!");
    }

    private function addMessageToJson($filename, $newMessage): string
    {
        $path = "chat-logs/$filename.json";

        $type = "new";

        // Проверка существования файла
        if (Storage::exists($path)) {
            // Чтение и декодирование файла
            $json = Storage::get($path);
            $data = json_decode($json, true);

            // Проверка наличия ключей
            $data['bot_id'] = $data['bot_id'] ?? null;
            $data['channel'] = $data['channel'] ?? null;
            $data['link'] = $data['link'] ?? null;
            $data['thread'] = $data['thread'] ?? null;
            $data['user'] = $data['user'] ?? null;
            $data['messages'] = $data['messages'] ?? [];
            // Добавление нового сообщения
            $data['messages'][] = [
                "message" => $newMessage["message"] ?? '-',
                'timestamp' => $newMessage["timestamp"] ?? null
            ];

            $type = "append";
        } else {
            // Создание новой структуры
            $data = [
                'bot_id' => $newMessage["bot_id"] ?? null,
                'channel' => $newMessage["channel"] ?? null,
                'thread' => $newMessage["thread"] ?? null,
                'link' => $newMessage["link"] ?? null,
                'user' => $newMessage["user"] ?? null,
                'messages' => [[
                    "message" => $newMessage["message"] ?? '-',
                    'timestamp' => $newMessage["timestamp"] ?? null
                ]],
            ];
        }

        // Сохранение обратно в файл
        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $type;
    }

    public function adminNotificationHandler($message, $query): bool
    {
        $type = $message->chat->type ?? null;

        if (is_null($type) || $type == "supergroup")
            return true;

        $botUser = $this->currentBotUser();
        $name = $botUser->name ?? $botUser->fio_from_telegram ?? $botUser->telegram_chat_id;

        $channel = env('TELEGRAM_ADMIN_CHANNEL');
        if (!is_null($channel)) {
            $botDomain = env("TELEGRAM_BOT_DOMAIN");
            $link = "https://t.me/$botDomain?start=" . base64_encode("003" . $this->currentBotUser()->telegram_chat_id);


            if (strlen($channel) > 6 && str_starts_with($channel, "-")) {

                $result = $this->addMessageToJson("chat-history-" . $this->currentBotUser()->telegram_chat_id, [
                    "bot_id" => $botDomain,
                    "channel" => $channel,
                    //"thread" => $thread,
                    "link" => $link,
                    "user" => [
                        "name" => $name,
                        "telegram_chat_id" => $botUser->telegram_chat_id
                    ],
                    'timestamp' => now()->toDateTimeString(),
                    "message" => $query
                ]);

                if ($result == "new")
                    $this->reply("Ваше сообщение будет доставлено администратору в течении 10 минут.");
                else
                    $this->replyAction();

                return true;
            }

        }
        return false;

    }

    public function next($name)
    {
        foreach ($this->routes as $route) {
            if (isset($route["name"]))
                if ($route["name"] == $name)
                    $this->next[] = [
                        "name" => $name,
                        "controller" => $this->controller ?? null,
                        "function" => $route["function"],
                        //  "arguments"=>$arguments??[]
                    ];
        }

        return $this;
    }

    public function controller($controller)
    {
        $this->controller = $controller;


        return $this;
    }

    public function route($path, $function, $name = null)
    {
        $this->routes[] = [
            "path" => $path,
            "is_service" => false,
            "controller" => $this->controller ?? null,
            "function" => $function,
            "name" => $name
        ];

        return $this;
    }

    public function slug($path, $function, $name = null)
    {
        $this->slugs[] = [
            "path" => $path,
            "is_service" => false,
            "controller" => $this->controller ?? null,
            "function" => $function,
            "name" => $name
        ];

        return $this;
    }

    public function location($function)
    {
        $this->routes[] = [
            "path" => "location",
            "is_service" => true,
            "function" => $function
        ];

        return $this;
    }

    public function fallback($function)
    {
        $this->routes[] = [
            "controller" => $this->controller ?? null,
            "path" => "fallback",
            "is_service" => true,
            "function" => $function
        ];

        return $this;
    }

    public function fallbackPhoto($function)
    {
        $this->routes[] = [
            "controller" => $this->controller ?? null,
            "path" => "fallback_photo",
            "is_service" => true,
            "function" => $function
        ];

        return $this;
    }

    public function fallbackAudio($function)
    {
        $this->routes[] = [
            "controller" => $this->controller ?? null,
            "path" => "fallback_audio",
            "is_service" => true,
            "function" => $function
        ];

        return $this;
    }

    public function fallbackSticker($function)
    {
        $this->routes[] = [
            "controller" => $this->controller ?? null,
            "path" => "fallback_sticker",
            "is_service" => true,
            "function" => $function
        ];

        return $this;
    }

    public function fallbackDocument($function)
    {
        $this->routes[] = [
            "controller" => $this->controller ?? null,
            "path" => "fallback_document",
            "is_service" => true,
            "function" => $function
        ];

        return $this;
    }

    public function fallbackVideo($function)
    {
        $this->routes[] = [
            "controller" => $this->controller ?? null,
            "path" => "fallback_video",
            "is_service" => true,
            "function" => $function
        ];

        return $this;
    }

    public function inline($function)
    {
        $this->inline = [
            "controller" => $this->controller ?? null,
            "function" => $function
        ];

        return $this;
    }
}
