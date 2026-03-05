<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Facades\BotManager;
use App\Facades\BotMethods;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telegram\Bot\FileUpload\InputFile;
use Illuminate\Support\Facades\Cache;

class TelegramController extends Controller
{
    const STATE_IDLE = 'idle';
    const STATE_ORDER_SERVICE = 'order_service';
    const STATE_ORDER_VOLUME = 'order_volume';
    const STATE_ORDER_FILES = 'order_files';
    const STATE_ORDER_NAME = 'order_name';
    const STATE_ORDER_CONTACTS = 'order_contacts';
    const STATE_ORDER_CALC_PAGES = 'order_calc_pages';
    const STATE_ORDER_CALC_FORMAT = 'order_calc_format';
    const STATE_ORDER_CALC_PHOTOS = 'order_calc_photos';
    const STATE_ORDER_CALC_PRINT = 'order_calc_print';
    const STATE_ORDER_CONFIRM = 'order_confirm';
    
    // Генератор первых строк
    const STATE_GEN_TOPIC = 'gen_topic';
    
    // Калькулятор
    const STATE_CALC_PAGES = 'calc_pages';
    const STATE_CALC_FORMAT = 'calc_format';
    const STATE_CALC_SOURCE = 'calc_source';
    const STATE_CALC_PHOTOS = 'calc_photos';
    const STATE_CALC_PRINT = 'calc_print';

    // Квиз "Какой жанр подходит"
    const STATE_QUIZ_Q1 = 'quiz_q1';
    const STATE_QUIZ_Q2 = 'quiz_q2';
    const STATE_QUIZ_Q3 = 'quiz_q3';
    const STATE_QUIZ_Q4 = 'quiz_q4';
    const STATE_QUIZ_Q5 = 'quiz_q5';

    public function getSelf(Request $request)
    {
        /*if (env("APP_DEBUG")) {
            $user = User::query()->first();
            $user->role = RoleEnum::ADMIN->value;
            $user->base_role = RoleEnum::ADMIN->value;
        } else {*/
            $user = User::query()
                ->find($request->botUser->id);
            $user->base_role = $user->role;
            // Log::info("ENV DEBUG FALSE" . print_r($user->toArray(), true));
        /*}*/


        return response()->json($user);
    }

    public function registerWebhooks(Request $request)
    {
        return response()->json(BotManager::bot()->setWebhook());
    }

    public function handler(Request $request)
    {
        BotManager::bot()->handler();

        return response()->json([
            "message" => "Ok"
        ]);
    }

    public function uploadAnyKindOfMedia(...$data)
    {
        $caption = $data[2] ?? null;
        $doc = $data[3] ?? null;
        $type = $data[4] ?? "document";

        $fileId = $doc->file_id ?? null;

        $token = env("TELEGRAM_BOT_TOKEN"); // поправь если у тебя другой конфиг


        $response = Http::get("https://api.telegram.org/bot{$token}/getFile", [
            'file_id' => $fileId,
        ]);

        if (!$response->ok()) {
            return;
        }

        $result = $response->json();

        if (!($result['ok'] ?? false)) {
            return;
        }

        $filePath = $result['result']['file_path'] ?? null;

        if (!$filePath) {
            return;
        }


        $fileUrl = "https://api.telegram.org/file/bot{$token}/{$filePath}";

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (empty($extension)) {
            $mimeType = $doc->mime_type ?? null;

            $extension = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'application/pdf' => 'pdf',
                'video/mp4' => 'mp4',
                default => null,
            };
        }


        $uuid = Str::uuid()->toString();
        $fileName = $uuid . '.' . ($extension ?? 'mp4');

        $fileResponse = Http::get($fileUrl);

        if (!$fileResponse->ok()) {
            return;
        }

        $fileContent = $fileResponse->body();

        // 5️⃣ Сохраняем в storage
        Storage::disk('local')->put("public/videos/{$fileName}", $fileContent);

        $videoLink = env("APP_URL") . "/storage/app/public/videos/$fileName";

        $botUser = BotManager::bot()->currentBotUser();
        $userInfo = $botUser->toTelegramText();
        $userLink = $botUser->getUserTelegramLink();

        $text = "✅ <b>Спасибо! Ваше поздравление принято!</b>

Чтобы не пропустить итоги акции, подписывайтесь на нас в социальных сетях:

📲 https://t.me/Newpeople_dnr

📲 https://vk.com/newpeople_dnr

<b>Мира вам и благополучия!</b> 🤍";

        BotMethods::bot()->sendMessage(
            $botUser->telegram_chat_id,
            $text
        );

        BotMethods::bot()
            ->sendMessage(
                env("TELEGRAM_ADMIN_CHANNEL"),
                "#информация_пользователя\n$userInfo" . $userLink . "\n\nСсылка на видео: $videoLink"
            );


    }

    public function getMyId(...$data)
    {
        $message = "Ваш чат id: <pre><code>" . ($data[0]->chat->id ?? 'не указан') . "</code></pre>\nИдентификатор топика: " . ($data[0]->message_thread_id ?? 'Не указан');

        BotManager::bot()
            ->reply($message);
    }

    public function aboutCommand(...$data)
    {
        BotManager::bot()
            ->replyPhoto(
                "Хочешь такой же бот для своего бизнеса? ",
                InputFile::create(public_path() . "/images/cashman.jpg"),
                [
                    [
                        [
                            "text" => "🔥Перейти в нашего бота для заявок",
                            "url" => "https://t.me/cashman_dn_bot"
                        ]
                    ],
                    [
                        [
                            "text" => "\xF0\x9F\x8D\x80Написать в тех. поддержку",
                            "url" => "https://t.me/EgorShipilov"
                        ],
                    ],

                ]
            );
    }

    public function helpCommand(...$data)
    {
        BotManager::bot()->reply("Как пользоваться ботом");
    }


    public function homePage(Request $request)
    {
        Inertia::setRootView("bot");
        return Inertia::render('Main');
    }

    public function startCommand()
    {
        // Устанавливаем команды для меню (подсказки при вводе /)
        $commands = [
            ['command' => 'start', 'description' => '▶️ Главное меню'],
        ];
        BotMethods::bot()->setMyCommands($commands);

        $userId = BotManager::bot()->currentBotUser()->id;
        Cache::forget("bot_user_state_{$userId}");
        Cache::forget("bot_user_order_data_{$userId}");

        $text = "👋 <b>Добро пожаловать!</b>\n\nЯ — ваш персональный проводник в мир книг. Я помогу вам оформить заявку на <b>написание мемуаров, биографии или истории вашего бизнеса</b>.\n\nВыберите нужный пункт меню ниже 👇";

        $keyboard = [
            [
                ["text" => "✍️ Оставить заявку"],
            ],
            [
                ["text" => "📚 Наши услуги и цены"],
            ],
            [
                ["text" => "📂 Портфолио / Примеры"],
            ],
            [
                ["text" => "📞 Контакты"],
            ]
        ];

        BotManager::bot()
            ->replyKeyboard($text, $keyboard);
    }

    public function orderCommand($keepData = false)
    {
        $userId = BotManager::bot()->currentBotUser()->id;
        Cache::put("bot_user_state_{$userId}", self::STATE_ORDER_SERVICE, now()->addHours(2));
        
        if (!$keepData) {
            Cache::forget("bot_user_order_data_{$userId}");
            Cache::forget("bot_user_calc_results_{$userId}");
        }

        $text = "📚 <b>НОВАЯ ЗАЯВКА</b>\n\nВыберите жанр вашей будущей книги или услугу, которая вас интересует:\n<i>(Если сомневаетесь — пройдите наш короткий тест)</i>";
        $keyboard = [
            [
                ["text" => "📜 Мемуары / Биография", "callback_data" => "service_biography"],
            ],
            [
                ["text" => "👨‍👩‍👧‍👦 История семьи", "callback_data" => "service_family"],
            ],
            [
                ["text" => "🏢 История компании", "callback_data" => "service_company"],
            ],
            [
                ["text" => "💼 Книга о проф. услугах", "callback_data" => "service_pro"],
            ],
            [
                ["text" => "✍️ Редактура готового текста", "callback_data" => "service_editing"],
            ],
            [
                ["text" => "❓ Не знаю, какой жанр выбрать (Пройти тест)", "callback_data" => "quiz_retry"],
            ],
        ];

        BotManager::bot()
            ->replyInlineKeyboard($text, $keyboard);
    }

    public function adminMenuCommand()
    {
        $botUser = BotManager::bot()->currentBotUser();
        
        // Разрешаем только администраторам (роль = 1) и менеджерам (роль = 2)
        if (!$botUser->isAdmin() && !$botUser->isManager()) {
            BotManager::bot()->reply("У вас нет доступа к этому меню.");
            return;
        }

        $adminUrl = env('APP_URL') . '/admin-auth'; // Ссылка на ваш интерфейс админки (через Inertia/Vue)

        $text = "👨‍💻 <b>Панель администратора</b>\n\nДобро пожаловать в админ-меню. Вы можете управлять заявками, проектами и пользователями прямо с телефона, открыв наше встроенное приложение.";

        $keyboard = [
            [
                ["text" => "🚀 Открыть админ-панель (Mini App)", "web_app" => ["url" => $adminUrl]],
            ],
            [
                ["text" => "📊 Статистика (в чат)", "callback_data" => "admin_stats"],
            ]
        ];

        BotManager::bot()
            ->replyInlineKeyboard($text, $keyboard);
    }

    public function handleWizard(...$data)
    {
        $botUser = BotManager::bot()->currentBotUser();
        $userId = $botUser->id;
        $state = Cache::get("bot_user_state_{$userId}", self::STATE_IDLE);
        
        // Получаем данные из запроса Telegram
        $update = request()->all();
        
        $query = $update['message']['text'] ?? 
                 $update['callback_query']['data'] ?? 
                 '';

        if ($query === 'admin_stats' && isset($update['callback_query'])) {
            if (!$botUser->isAdmin() && !$botUser->isManager()) {
                BotManager::bot()->reply("У вас нет доступа к этой функции.");
                return;
            }

            $totalLeads = \App\Models\Lead::count();
            $newLeads = \App\Models\Lead::where('status', 'new')->count();
            $totalUsers = User::count();

            $statsText = "📊 <b>Краткая статистика</b>\n\n" .
                         "👤 Всего пользователей: <b>{$totalUsers}</b>\n" .
                         "📝 Всего заявок: <b>{$totalLeads}</b>\n" .
                         "🔥 Новых заявок (не обработано): <b>{$newLeads}</b>\n\n" .
                         "<i>Подробности смотрите в Mini App 🚀</i>";

            BotManager::bot()->reply($statsText);
            
            // Убираем часики на кнопке инлайн клавиатуры
            $callbackId = $update['callback_query']['id'];
            $bot = BotMethods::bot();
            $bot->answerCallbackQuery([
                'callback_query_id' => $callbackId
            ]);
            return;
        }

        Log::info("Bot Debug: userId={$userId}, state={$state}, query={$query}");

        if (empty($query) && isset($update['message']['contact'])) {
            $contact = $update['message']['contact'];
            $phone = $contact['phone_number'] ?? '';
            $firstName = $contact['first_name'] ?? '';
            $lastName = $contact['last_name'] ?? '';
            $query = trim("$firstName $lastName $phone");
            
            // Если номер не начинается с +, добавим его (Telegram иногда отдает без +)
            if (!empty($phone) && $phone[0] !== '+') {
                 $phone = '+' . $phone;
                 // Пересобираем строку с правильным форматом номера
                 $query = trim("$firstName $lastName $phone");
            }
        }

        // Отвечаем на callback и удаляем кнопки (чтобы не кликали дважды)
        if (isset($update['callback_query']['id'])) {
            $chatId = $update['callback_query']['message']['chat']['id'] ?? null;
            $messageId = $update['callback_query']['message']['message_id'] ?? null;
            
            BotMethods::bot()->answerCallbackQuery($update['callback_query']['id']);
            
            // Удаляем инлайн-клавиатуру из сообщения, где нажали кнопку
            if ($chatId && $messageId) {
                BotMethods::bot()->editInlineKeyboard($chatId, $messageId, []);
            }
        }

        // Обработка специальных callbacks
        if (($update['callback_query']['data'] ?? '') === 'fix_price') {
            $this->orderCommand();
            return;
        }

        if (($update['callback_query']['data'] ?? '') === 'calc_retry') {
            $this->calcCommand();
            return;
        }

        $callbackData = $update['callback_query']['data'] ?? '';
        if (str_starts_with($callbackData, 'quiz_order:')) {
            $parts = explode(':', $callbackData);
            $genre = $parts[1] ?? 'unknown';
            $this->startOrderWithGenre($genre);
            return;
        }

        if (($update['callback_query']['data'] ?? '') === 'quiz_retry') {
            $this->quizCommand();
            return;
        }

        if (($update['callback_query']['data'] ?? '') === 'gen_retry') {
            $this->generatorCommand();
            return;
        }

        if (str_starts_with($callbackData, 'gen_select:')) {
            $variant = (int)explode(':', $callbackData)[1];
            $this->selectGeneratorVariant($userId, $variant);
            return;
        }

        if (($update['callback_query']['data'] ?? '') === 'gen_order') {
            $this->startOrderWithGeneratorData($userId);
            return;
        }

        if ($callbackData === 'order_go_gen') {
            $this->generatorCommand();
            return;
        }

        if ($callbackData === 'order_skip_gen') {
            $this->continueOrderAfterGenre($userId);
            return;
        }

        if (($update['callback_query']['data'] ?? '') === 'gen_home') {
            $this->startCommand();
            return;
        }



        if ($state === self::STATE_IDLE) {
            return;
        }

        // Защита от двойных кликов: если пришел callback, но мы уже не на этапе выбора услуги
        if (isset($update['callback_query']) && $state !== self::STATE_ORDER_SERVICE) {
            // Исключаем специальные системные колбэки, которые должны работать всегда
            $specialCallbacks = ['fix_price', 'calc_retry', 'quiz_retry', 'confirm_yes', 'confirm_no'];
            $isSpecial = false;
            foreach ($specialCallbacks as $sc) {
                if (str_starts_with($callbackData, $sc) || str_starts_with($callbackData, 'quiz_order:')) {
                    $isSpecial = true;
                    break;
                }
            }
            
            if (!$isSpecial) {
                return;
            }
        }

        switch ($state) {
            case self::STATE_ORDER_SERVICE:
                $this->step1Service($userId, $query);
                break;
            case self::STATE_ORDER_VOLUME:
                $this->step2Volume($userId, $query);
                break;
            case self::STATE_ORDER_FILES:
                $media = $data[3] ?? null; 
                $this->step3Files($userId, $query, $media);
                break;
            case self::STATE_ORDER_NAME:
                $this->step3_5Name($userId, $query);
                break;
            case self::STATE_ORDER_CONTACTS:
                $this->step4Contacts($userId, $query);
                break;
            case self::STATE_ORDER_CALC_PAGES:
                $this->stepOrderCalcPages($userId, $query);
                break;
            case self::STATE_ORDER_CALC_FORMAT:
                $this->stepOrderCalcFormat($userId, $query);
                break;
            case self::STATE_ORDER_CALC_PRINT:
                $this->stepOrderCalcPrint($userId, $query);
                break;
            case self::STATE_ORDER_CALC_PHOTOS:
                $this->stepOrderCalcPhotos($userId, $query);
                break;
            case self::STATE_ORDER_CONFIRM:
                $this->step5Confirm($userId, $query);
                break;
            // Калькулятор
            case self::STATE_CALC_PAGES:
                $this->stepCalcPages($userId, $query);
                break;
            case self::STATE_CALC_FORMAT:
                $this->stepCalcFormat($userId, $query);
                break;
            case self::STATE_CALC_SOURCE:
                $this->stepCalcSource($userId, $query);
                break;
            case self::STATE_CALC_PHOTOS:
                $this->stepCalcPhotos($userId, $query);
                break;
            case self::STATE_CALC_PRINT:
                $this->stepCalcPrint($userId, $query);
                break;
            case self::STATE_QUIZ_Q1:
                $this->stepQuizQ1($userId, $query);
                break;
            case self::STATE_QUIZ_Q2:
                $this->stepQuizQ2($userId, $query);
                break;
            case self::STATE_QUIZ_Q3:
                $this->stepQuizQ3($userId, $query);
                break;
            case self::STATE_QUIZ_Q4:
                $this->stepQuizQ4($userId, $query);
                break;
            case self::STATE_QUIZ_Q5:
                $this->stepQuizQ5($userId, $query);
                break;
            // Генератор
            case self::STATE_GEN_TOPIC:
                $this->stepGenTopic($userId, $query);
                break;
        }
    }

    private function startOrderWithGeneratorData($userId)
    {
        $topic = Cache::get("bot_user_gen_topic_{$userId}", 'Не указана');
        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        
        // Помечаем, что тема пришла из генератора
        $orderData['volume'] = "Тема из генератора: {$topic}";
        
        // Если уже есть выбранный вариант и стиль, добавим их
        $selectedStyle = Cache::get("bot_user_gen_style_{$userId}");
        $selectedText = Cache::get("bot_user_gen_text_{$userId}");
        
        if ($selectedStyle) {
            $orderData['style'] = $selectedStyle;
            $orderData['first_lines'] = $selectedText;
        }

        Cache::put("bot_user_order_data_{$userId}", $orderData, now()->addHours(2));

        if (isset($orderData['service'])) {
            $this->continueToFiles($userId);
        } else {
            $this->orderCommand(true); 
        }
    }

    private function selectGeneratorVariant($userId, $variant)
    {
        $variants = Cache::get("bot_user_gen_variants_{$userId}", []);
        $topic = Cache::get("bot_user_gen_topic_{$userId}", 'Не указана');
        
        if (!isset($variants[$variant])) {
            BotManager::bot()->reply("Извините, срок действия этого варианта истек. Попробуйте сгенерировать заново.");
            return;
        }

        $styles = [
            1 => "Личный / Мемуарный",
            2 => "Драматичный / Нуар",
            3 => "Философский / Поэтичный"
        ];

        $style = $styles[$variant];
        $text = $variants[$variant];

        Cache::put("bot_user_gen_style_{$userId}", $style, now()->addHours(1));
        Cache::put("bot_user_gen_text_{$userId}", $text, now()->addHours(1));

        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        $orderData['volume'] = "Тема из генератора: {$topic}";
        $orderData['style'] = $style;
        $orderData['first_lines'] = $text;
        
        // Если жанр еще не выбран, попробуем определить его по стилю
        if (!isset($orderData['service'])) {
            $genreMap = [
                1 => "Биографический нон-фикшн",
                2 => "Художественная биография",
                3 => "Философское эссе / История семьи"
            ];
            $orderData['service'] = $genreMap[$variant];
        }

        Cache::put("bot_user_order_data_{$userId}", $orderData, now()->addHours(2));

        BotManager::bot()->reply("✅ <b>Выбран вариант №{$variant} ({$style})</b>\n\n«{$text}»\n\nОтличный выбор! Мы закрепили этот стиль за вашим проектом.");
        
        $this->continueToFiles($userId);
    }

    private function suggestGenerator($userId, $serviceName)
    {
        $text = "Отличный выбор! Жанр вашей будущей книги: <b>{$serviceName}</b>.\n\n" .
                "Хотите, чтобы наш ИИ прямо сейчас сгенерировал <b>три варианта первой строки</b> в разных стилях (мемуарный, драматичный, философский)?\n\n" .
                "Это поможет вам лучше почувствовать дух вашего будущего произведения!";
        
        $keyboard = [
            [
                ["text" => "✨ Сгенерировать зачины", "callback_data" => "order_go_gen"],
            ],
            [
                ["text" => "➡️ Продолжить оформление", "callback_data" => "order_skip_gen"],
            ]
        ];

        BotManager::bot()->replyInlineKeyboard($text, $keyboard);
    }

    private function continueOrderAfterGenre($userId)
    {
        Cache::put("bot_user_state_{$userId}", self::STATE_ORDER_VOLUME, now()->addHours(2));

        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        $isEditing = isset($orderData['service']) && $orderData['service'] === 'Редактура готового текста';

        if ($isEditing) {
            $text = "📄 <b>Статус рукописи</b>\n\nРасскажите о вашем тексте:\nГотов ли он полностью или требуется доработка?";
            $keyboard = [
                [["text" => "✅ Текст полностью готов"]],
                [["text" => "⚙️ Требуется доработка / Правка"]]
            ];
        } else {
            $text = "🏁 <b>Стадия работы</b>\n\nРасскажите, на каком этапе находится ваш проект?\nЕсть ли у вас уже какие-то наработки?";
            $keyboard = [
                [["text" => "🆕 Начинаем с нуля"]],
                [["text" => "📝 Есть черновики / Записи"]]
            ];
        }

        BotManager::bot()->replyKeyboard($text, $keyboard);
    }

    private function continueToFiles($userId)
    {
        Cache::put("bot_user_state_{$userId}", self::STATE_ORDER_FILES, now()->addHours(2));

        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        $isEditing = isset($orderData['service']) && $orderData['service'] === 'Редактура готового текста';

        if ($isEditing) {
            $text = "📎 <b>Загрузка рукописи</b>\n\n<b>Прикрепите ваш текст</b> (Word, PDF, TXT или любой другой формат).\n\nМожно отправить несколько файлов, если текст разбит на части.\nКогда закончите, нажмите <b>«Готово»</b> 👇";
        } else {
            $text = "📎 <b>Материалы и файлы</b>\n\nЕсли у вас есть фото, голосовые заметки, черновики или любые другие файлы по теме книги — <b>прикрепите их прямо сейчас</b>.\n\nКоличество файлов не ограничено.\nКогда закончите, нажмите <b>«Готово»</b> 👇";
        }

        $keyboard = [
            [["text" => "✅ Готово"]],
            [["text" => "⏭ Пропустить"]]
        ];

        BotManager::bot()->replyKeyboard($text, $keyboard);
    }

    private function step1Service($userId, $query)
    {
        $services = [
            'service_biography' => 'Мемуары / Биография',
            'service_family' => 'История семьи',
            'service_company' => 'История компании',
            'service_pro' => 'Книга о проф. услугах',
            'service_editing' => 'Редактура готового текста',
        ];

        if (!isset($services[$query])) {
            BotManager::bot()->reply("Пожалуйста, выберите услугу из списка выше.");
            return;
        }

        $orderData = ['service' => $services[$query]];
        Cache::put("bot_user_order_data_{$userId}", $orderData, now()->addHours(2));
        
        // Для редактуры готового текста пропускаем генератор и сразу переходим к следующему шагу
        if ($query === 'service_editing') {
            $this->continueOrderAfterGenre($userId);
        } else {
            $this->suggestGenerator($userId, $services[$query]);
        }
    }

    private function step2Volume($userId, $text)
    {
        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        $orderData['volume'] = $text;
        Cache::put("bot_user_order_data_{$userId}", $orderData, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_ORDER_FILES, now()->addHours(2));

        if ($text === "🆕 Начинаем с нуля") {
            $replyText = "Принято! Если есть какие-то файлы с подробностями истории, то прикрепите их:";
        } else {
            $replyText = "Принято! Прикрепите файлы:";
        }

        $keyboard = [
            [["text" => "✅ Готово"]],
            [["text" => "⏭ Пропустить"]]
        ];

        BotManager::bot()->replyKeyboard($replyText, $keyboard);
    }

    private function step3Files($userId, $query, $media = null)
    {
        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        if (!isset($orderData['files_ids'])) {
            $orderData['files_ids'] = [];
        }
        if (!isset($orderData['files_msg_ids'])) {
            $orderData['files_msg_ids'] = [];
        }

        if ($query === "✅ Готово") {
            if (empty($orderData['files_ids'])) {
                $orderData['files'] = "Не прикреплены";
            } else {
                $orderData['files'] = "Прикреплено файлов: " . count($orderData['files_ids']);
            }
        } elseif ($query === "⏭ Пропустить") {
            $orderData['files'] = "Не прикреплены";
            $orderData['files_ids'] = [];
            $orderData['files_msg_ids'] = [];
        } elseif ($media) {
            $update = request()->all();
            $messageId = $update['message']['message_id'] ?? null;
            
            $fileId = is_array($media) ? (end($media)->file_id ?? 'unknown') : ($media->file_id ?? 'unknown');
            $orderData['files_ids'][] = $fileId;
            if ($messageId) {
                $orderData['files_msg_ids'][] = $messageId;
            }
            
            Cache::put("bot_user_order_data_{$userId}", $orderData, now()->addHours(2));
            
            // BotManager::bot()->reply("✅ Файл получен! Можете отправить еще файлы или нажмите «Готово», когда закончите.");
            return; // Остаемся в этом состоянии
        } else {
            BotManager::bot()->reply("Пожалуйста, отправьте файл или нажмите кнопку «Готово» / «Пропустить».");
            return;
        }

        Cache::put("bot_user_order_data_{$userId}", $orderData, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_ORDER_NAME, now()->addHours(2));

        $keyboard = [
            [
                ["text" => "❌ Начать заново"],
            ],
        ];

        BotManager::bot()->replyKeyboard(
            "👤 <b>Знакомство</b>\n\nКак к вам обращаться? Введите ваше имя:", 
            $keyboard
        );
    }

    private function step3_5Name($userId, $text)
    {
        if ($text === "❌ Начать заново") {
            $this->startCommand();
            return;
        }

        if (empty($text) || strlen($text) < 2) {
            BotManager::bot()->reply("Пожалуйста, введите ваше имя (минимум 2 символа).");
            return;
        }

        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        $orderData['client_name'] = $text;
        Cache::put("bot_user_order_data_{$userId}", $orderData, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_ORDER_CONTACTS, now()->addHours(2));

        $keyboard = [
            [
                ["text" => "📱 Поделиться номером", "request_contact" => true],
            ],
            [
                ["text" => "❌ Начать заново"],
            ],
        ];

        BotManager::bot()->replyKeyboard(
            "📱 <b>Контакт для связи</b>\n\nПриятно познакомиться, {$text}!\n\nПожалуйста, поделитесь вашим номером телефона (кнопка ниже) или отправьте @username:", 
            $keyboard
        );
    }

    private function step4Contacts($userId, $text)
    {
        if ($text === "❌ Начать заново") {
            $this->startCommand();
            return;
        }

        $botUser = BotManager::bot()->currentBotUser();
        $update = request()->all();
        
        $phone = '';
        if (isset($update['message']['contact'])) {
            $phone = $update['message']['contact']['phone_number'];
        } else {
            // Если ввели текст, очистим от лишнего и проверим на телефон
            $phone = $text;
        }

        // Если номер не пустой, сохраним его в профиль пользователя
        if (!empty($phone)) {
            $botUser->phone = $phone;
            $botUser->save();
        }

        // Если телефон не указан (например, просто текст отправили), 
        // попробуем взять username
        $contactDisplay = $phone;
        if (empty($phone) || strlen($phone) < 5) {
            $contactDisplay = "@" . ($botUser->name ?? "id" . $botUser->telegram_chat_id);
        }

        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        $orderData['contacts'] = $contactDisplay;
        Cache::put("bot_user_order_data_{$userId}", $orderData, now()->addHours(2));

        // Если расчет уже был сделан ранее (из отдельного калькулятора), переходим сразу к итогу
        $calcResults = Cache::get("bot_user_calc_results_{$userId}");
        if ($calcResults) {
            $this->showOrderSummary($userId);
            return;
        }

        Cache::put("bot_user_state_{$userId}", self::STATE_ORDER_CALC_PAGES, now()->addHours(2));

        $text = "Приятно познакомиться! Почти готово. 🏁\n\nДавайте примерно рассчитаем стоимость вашего проекта. Введите примерное <b>количество страниц</b> будущей книги (минимум 20, кратно 4, например: 20) или нажмите «Пропустить»:";
        $keyboard = [[["text" => "⏭ Пропустить"]]];
        BotManager::bot()->replyKeyboard($text, $keyboard);
    }

    private function stepOrderCalcPages($userId, $text)
    {
        if ($text === "⏭ Пропустить") {
            $this->showOrderSummary($userId);
            return;
        }

        $pages = (int) filter_var($text, FILTER_SANITIZE_NUMBER_INT);
        if ($pages < 20) {
            BotManager::bot()->reply("⚠️ Минимальный объём книги — <b>20 страниц</b>.\nПожалуйста, введите количество страниц от 20 или нажмите «Пропустить».");
            return;
        }

        // Число страниц должно быть кратно 4
        if ($pages % 4 !== 0) {
            $rounded = (int)(ceil($pages / 4) * 4);
            BotManager::bot()->reply("⚠️ Объём книги должен быть кратен 4 страницам. Ближайшее значение: <b>{$rounded}</b>.\n\nВведите количество страниц (кратное 4) или нажмите «Пропустить»:");
            return;
        }

        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        $orderData['calc_pages'] = $pages;
        Cache::put("bot_user_order_data_{$userId}", $orderData, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_ORDER_CALC_FORMAT, now()->addHours(2));

        $keyboard = [
            [["text" => "📄 Формат А5 (148×210 мм)"]],
            [["text" => "📋 Формат А4 (210×297 мм)"]],
            [["text" => "⏭ Пропустить"]],
        ];
        BotManager::bot()->replyKeyboard("📐 Выберите формат книги:", $keyboard);
    }

    private function stepOrderCalcFormat($userId, $text)
    {
        if ($text === "⏭ Пропустить") {
            $this->stepOrderCalcPrint($userId, "⏭ Пропустить");
            return;
        }

        $formats = [
            "📄 Формат А5 (148×210 мм)" => 'A5',
            "📋 Формат А4 (210×297 мм)" => 'A4',
        ];

        $format = $formats[$text] ?? 'A5';
        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        $orderData['calc_format'] = $format;
        Cache::put("bot_user_order_data_{$userId}", $orderData, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_ORDER_CALC_PHOTOS, now()->addHours(2));

        $keyboard = [
            [["text" => "🚫 Без фото"]],
            [["text" => "📸 До 20 фото"]],
            [["text" => "📸 20-40 фото"]],
            [["text" => "📸 40-60 фото"]],
            [["text" => "⏭ Пропустить"]],
        ];
        BotManager::bot()->replyKeyboard("🖼 Сколько фотографий планируется в книге?\n<i>(Обработка и вставка: 1 000 ₽ за каждые 20 шт.)</i>", $keyboard);
    }

    private function stepOrderCalcPhotos($userId, $text)
    {
        if ($text === "⏭ Пропустить") {
            $this->stepOrderCalcPrint($userId, "⏭ Пропустить");
            return;
        }

        $photos = 0;
        if ($text === "📸 До 20 фото") $photos = 20;
        if ($text === "📸 20-40 фото") $photos = 40;
        if ($text === "📸 40-60 фото") $photos = 60;

        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        $orderData['calc_photos'] = $photos;
        Cache::put("bot_user_order_data_{$userId}", $orderData, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_ORDER_CALC_PRINT, now()->addHours(2));

        $keyboard = [
            [["text" => "💻 Только электронная версия (PDF)"]],
            [["text" => "⬛ Печать ч/б + электронная версия"]],
            [["text" => "🎨 Цветная печать + электронная версия"]],
            [["text" => "⏭ Пропустить"]],
        ];
        BotManager::bot()->replyKeyboard("🖨 Выберите тип издания:", $keyboard);
    }

    private function stepOrderCalcPrint($userId, $text)
    {
        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        $pages  = (int)($orderData['calc_pages'] ?? 0);
        $format = $orderData['calc_format'] ?? 'A5';
        $source = $orderData['volume'] ?? '';

        if ($text !== "⏭ Пропустить" && $pages > 0) {
            $isEditing = isset($orderData['service']) && $orderData['service'] === 'Редактура готового текста';
            $basePricePerPage = ($format === 'A4') ? ($isEditing ? 70 : 225) : ($isEditing ? 45 : 150);
            $writingPrice = $pages * $basePricePerPage;

            $ePrice = 0; 
            $coverPrice = 500; // Базовая цена обложки

            $printPrice = 0;
            $bindingPrice = 0;
            $printLabel = '';
            switch ($text) {
                case "💻 Только электронная версия (PDF)":
                    $printLabel = "Электронная версия (PDF)";
                    break;
                case "⬛ Печать ч/б + электронная версия":
                    $printPrice = ($pages / 20) * 800;
                    $bindingPrice = 700;
                    $printLabel = "Ч/б печать + переплёт";
                    break;
                case "🎨 Цветная печать + электронная версия":
                    $printPrice = ($pages / 20) * 1200;
                    $bindingPrice = 700;
                    $printLabel = "Цветная печать + переплёт";
                    break;
                default:
                    $printLabel = $text;
            }

            $photoCount = (int)($orderData['calc_photos'] ?? 0);
            $photoPrice = (int)(ceil($photoCount / 20) * 1000);
            $coverPrice = 500;
            $ePrice = 0;

            $totalMin = $writingPrice + $ePrice + $printPrice + $bindingPrice + $coverPrice + $photoPrice;
            $totalMax = (int)($totalMin * 1.15);
            $minFmt = number_format($totalMin, 0, '.', ' ');
            $maxFmt = number_format($totalMax, 0, '.', ' ');

            Cache::put("bot_user_calc_results_{$userId}", [
                'pages'  => $pages,
                'format' => $format,
                'source' => $source,
                'photos' => $photoCount,
                'print'  => $printLabel,
                'range'  => "от {$minFmt} до {$maxFmt} ₽"
            ], now()->addHours(2));
        }

        $this->showOrderSummary($userId);
    }

    private function showOrderSummary($userId)
    {
        Cache::put("bot_user_state_{$userId}", self::STATE_ORDER_CONFIRM, now()->addHours(2));
        $orderData = Cache::get("bot_user_order_data_{$userId}", []);

        // Удаляем ReplyKeyboard
        BotManager::bot()->replyKeyboard("Принято! Проверьте данные перед отправкой:", null);

        // Подготовка данных для summary
        $clientName = $orderData['client_name'] ?? 'Не указано';
        $contacts = $orderData['contacts'] ?? 'Не указано';
        $service = $orderData['service'] ?? 'Не указано';
        $volume = $orderData['volume'] ?? 'Не указано';
        $files = $orderData['files'] ?? 'Не прикреплены';
        $style = $orderData['style'] ?? null;
        $lines = $orderData['first_lines'] ?? null;
        
        $calcResults = Cache::get("bot_user_calc_results_{$userId}");

        $summary = "📋 <b>Ваша заявка сформирована</b>\n\n" .
            "👤 <b>Заказчик:</b> {$clientName}\n" .
            "📞 <b>Контакты:</b> {$contacts}\n" .
            "➖➖➖➖➖➖➖➖\n" .
            "� <b>Услуга:</b> {$service}\n" .
            "� <b>Детали:</b> {$volume}\n";

        if ($style) {
            $summary .= "🎨 <b>Стиль:</b> {$style}\n";
            $summary .= "🖋 <b>Зачин:</b> <i>«{$lines}»</i>\n";
        }
        
        if ($calcResults) {
            $summary .= "➖➖➖➖➖➖➖➖\n" .
                "🧮 <b>Параметры:</b> {$calcResults['pages']} стр. ({$calcResults['format']})\n";
            if (isset($calcResults['photos']) && $calcResults['photos'] > 0) {
                $summary .= "🖼 <b>Фотографии:</b> {$calcResults['photos']} шт.\n";
            }
            $summary .= "💰 <b>Ориентир. бюджет:</b> {$calcResults['range']}\n";
        }

        $summary .= "� <b>Файлы:</b> {$files}\n\n" .
                    "<i>Всё верно? Отправляем менеджеру?</i>";

        $keyboard = [
            [["text" => "✅ Да, всё верно", "callback_data" => "confirm_yes"]],
            [["text" => "❌ Начать заново", "callback_data" => "confirm_no"]],
        ];

        BotManager::bot()->replyInlineKeyboard($summary, $keyboard);
    }

    private function step5Confirm($userId, $query)
    {
        if ($query === "confirm_yes") {
            $orderData = Cache::get("bot_user_order_data_{$userId}", []);
            $botUser = BotManager::bot()->currentBotUser();
            
            // Сохраняем лид в базу данных
            $volumeStage = $orderData['volume'] ?? 'Не указано';
            if (isset($orderData['style'])) {
                $style = $orderData['style'];
                $lines = $orderData['first_lines'] ?? '';
                $volumeStage .= "\nСтиль: {$style}\nЗачин: {$lines}";
            }
            
            $calcResults = Cache::get("bot_user_calc_results_{$userId}");
            if ($calcResults) {
                $volumeStage .= "\n\n🧮 Расчет: {$calcResults['pages']} стр, {$calcResults['source']}, {$calcResults['print']}\n💰 Оценка: {$calcResults['range']}";
            }

            // Сохраняем лид в базу данных
            \App\Models\Lead::create([
                'user_id' => $botUser->id,
                'client_name' => $orderData['client_name'] ?? 'Не указано',
                'service_type' => $orderData['service'] ?? 'Не указано',
                'volume_stage' => $volumeStage,
                'files' => $orderData['files'] ?? 'Не прикреплены',
                'contacts' => $orderData['contacts'] ?? 'Не указано',
                'status' => 'new',
            ]);

            $userInfo = $botUser->toTelegramText();

            $adminText = "⚡️ <b>НОВЫЙ ЛИД / ЗАЯВКА</b>\n" .
                "➖➖➖➖➖➖➖➖➖➖\n" .
                "👤 <b>Заказчик:</b> " . ($orderData['client_name'] ?? 'Не указано') . "\n" .
                "📞 <b>Связь:</b> " . ($orderData['contacts'] ?? 'Не указано') . "\n" .
                "➖➖➖➖➖➖➖➖➖➖\n" .
                "📚 <b>Услуга:</b> " . ($orderData['service'] ?? 'Не указано') . "\n" .
                "📊 <b>Детали проекта:</b>\n" . $volumeStage . "\n" .
                "📎 <b>Материалы:</b> " . ($orderData['files'] ?? 'Не прикреплены') . "\n" .
                "➖➖➖➖➖➖➖➖➖➖\n" .
                "🔗 <b>Профиль:</b> " . $botUser->getUserTelegramLink();

            BotMethods::bot()->sendMessage(
                env("TELEGRAM_ADMIN_CHANNEL"),
                $adminText
            );

            // Пересылаем сами файлы админу
            if (!empty($orderData['files_msg_ids'])) {
                $adminChannel = env("TELEGRAM_ADMIN_CHANNEL");
                $userChatId = $botUser->telegram_chat_id;
                
                foreach ($orderData['files_msg_ids'] as $msgId) {
                    BotMethods::bot()->forwardMessage($adminChannel, $userChatId, $msgId);
                }
            }

            $keyboard = [
                [
                    ["text" => "✍️ Оставить заявку"],
                ],
                [
                    ["text" => "📚 Наши услуги и цены"],
                    ["text" => "📂 Портфолио / Примеры"],
                ],
                [
                    ["text" => "📞 Контакты"],
                ]
            ];

            BotManager::bot()->replyKeyboard("🎉 <b>Спасибо, ваша заявка принята!</b>\n\nВ ближайшее время с вами свяжется наш менеджер для уточнения деталей.\n\nА пока вы можете ознакомиться с нашими работами 👇", $keyboard);

            Cache::forget("bot_user_state_{$userId}");
            Cache::forget("bot_user_order_data_{$userId}");

        } elseif ($query === "confirm_no") {
            $this->orderCommand();
        } else {
            BotManager::bot()->reply("Пожалуйста, подтвердите отправку заявки.");
        }
    }

    public function servicesCommand()
    {
        $text = "📚 <b>НАШИ УСЛУГИ И ЦЕНЫ</b>\n" .
            "➖➖➖➖➖➖➖➖➖➖\n" .
            "<i>⚡ Минимальный объём — от 20 страниц.</i>\n\n" .

            "📖 <b>1. Мемуары и биографии</b>\n" .
            "<i>• написание + редактура</i>\n" .
            "📦 Стандарт: <b>150 ₽/стр (А5) · 225 ₽/стр (А4)</b>\n" .
            "Превратим ваши воспоминания в захватывающую книгу. Интервью, редактура, верстка.\n\n" .
            
            "🌳 <b>2. История семьи (Родословная)</b>\n" .
            "<i>• исследование + архивная работа</i>\n" .
            "📦 Стандарт: <b>150 ₽/стр (А5) · 225 ₽/стр (А4)</b>\n" .
            "Глубокое исследование корней, архивная работа и создание семейной реликвии.\n\n" .
            
            "🏢 <b>3. История компании / Бренда</b>\n" .
            "<i>• написание + оформление</i>\n" .
            "📦 Стандарт: <b>150 ₽/стр (А5) · 225 ₽/стр (А4)</b>\n" .
            "Книга о вашем бизнесе: от идеи до успеха. Идеально для партнеров и сотрудников.\n\n" .
            
            "🎓 <b>4. Книга эксперта</b>\n" .
            "<i>• нон-фикшн, методики, кейсы</i>\n" .
            "📦 Стандарт: <b>150 ₽/стр (А5) · 225 ₽/стр (А4)</b>\n" .
            "Упакуем вашу экспертность, методики и кейсы в качественный нон-фикшн.\n\n" .
            
            "✍️ <b>5. Редактура и корректура</b>\n" .
            "<i>• вычитка + стилистическая правка</i>\n" .
            "📦 Стандарт: <b>45 ₽/стр (А5) · 70 ₽/стр (А4)</b>\n" .
            "Подготовка вашего текста к печати.\n\n" .

            "➖➖➖➖➖➖➖➖➖➖\n" .
            "🖨 <b>Печать и оформление:</b>\n" .
            "• Ч/Б печать (за 20 стр.): <b>+800 ₽</b>\n" .
            "• Цветная печать (за 20 стр.): <b>+1 200 ₽</b>\n" .
            "• Твёрдый переплёт: <b>+700 ₽</b>\n" .
            "• Дизайн обложки: <b>+500 ₽</b>\n" .
            "• Обработка и вставка фото (за 20 шт.): <b>+1 000 ₽</b>\n\n" .
            "<i>⚠️ Объём книги/журнала должен быть кратен 4 страницам</i>";

        $keyboard = [
            [
                ["text" => "🧮 Рассчитать стоимость", "callback_data" => "calc_retry"],
            ],
            [
                ["text" => "✍️ Оставить заявку"],
            ]
        ];

        BotManager::bot()->replyInlineKeyboard($text, $keyboard);
    }

    public function portfolioCommand()
    {
        $text = "📂 <b>ПОРТФОЛИО</b>\n\n" . 
                "Мы подготовили для вас лучшие примеры наших работ, чтобы вы могли оценить качество и стиль.\n\n" .
                "<i>🚧 Раздел находится в стадии наполнения. Скоро здесь появятся ссылки на кейсы!</i>";

        BotManager::bot()->reply($text);
    }



    public function contactsCommand()
    {
        $text = "📞 <b>КОНТАКТЫ</b>\n" .
            "➖➖➖➖➖➖➖➖\n\n" .
            "Мы всегда на связи и готовы обсудить ваш проект!\n\n" .
            //"📍 <b>Адрес:</b> г. Донецк, ул. Артема, д. 1\n" .
            "📱 <b>Телефон:</b> +7 (949) 327-29-23\n" .
            "✉️ <b>Email:</b> daniilnazarenko313@mail.ru\n" .
            "💬 <b>Telegram:</b> @sentinel_21st\n\n" .
            "⏰ <b>Часы работы:</b> Пн-Пт с 9:00 до 17:00";

        BotManager::bot()->reply($text);
    }

    public function runApologize()
    {
        ini_set('max_execution_time', '300');
        $text = "
        🔧 Мы всё починили!

Ранее у ряда пользователей возникла проблема с отправкой видео через бот. Сейчас мы исправили ошибки, и видео принимаются в штатном режиме.

➡️ Если у вас ранее не получилось загрузите своё видео, то нажмите команду /start и пройдите процедуру заново.
        ";

        $keyboard = [
            [
                [
                    "text" => "➡Начать заново",
                    "callback_data" => "/start"
                ],
            ],
        ];

        $botUsers = User::query()
            ->whereNotNull("telegram_chat_id")
            ->get();

        foreach ($botUsers as $user) {
            BotManager::bot()
                ->sendInlineKeyboard(
                    $user->telegram_chat_id,
                    $text,
                    $keyboard
                );
            sleep(1);
        }

    }

    public function runMiniApp()
    {
        $text = "
📌<b>Требования к видео:</b>
1. Ориентация любая (вертикальная/горизонтальная)
2. Хронометраж — 1-3 минуты
3. Чёткий звук и изображение
4. В кадре только один человек

📎 <b>Рекомендации к содержанию видео:</b>
1. Начните с обращения в единственном числе, например: <em>«Дорогой защитник!»</em>, <em>«Здравствуй, солдат!»</em> и т.д. Ваше видео будет адресовано только одному бойцу, а не нескольким.
2. Расскажите немного о себе: как вас зовут, из какого вы города. Так боец сможет виртуально познакомиться с вами.
3. Поблагодарите бойца за его нелёгкий труд. Нашим солдатам важно знать, что их службу ценят, а их самих поддерживают в каждом уголке страны.
4. Поздравьте с Днём защитника Отечества и произнесите самые искренние пожелания.

<b>Заполните небольшую анкету и пришлите своё видео. Для этого нажмите кнопку ниже.</b>

⬇️⬇️⬇️
        ";

        $keyboard = [
            [
                [
                    "text" => "Заполнить анкету",
                    "web_app" => [
                        "url" => env("APP_URL") . "/bot#/"
                    ]
                ],
            ],
        ];

        $slash = env("APP_DEBUG") ? "\\" : "/";


        BotManager::bot()
            ->replyPhoto(
                $text,
                InputFile::create(
                    public_path() . $slash . "photo_2026-01-28_16-29-01.jpg",
                    "photo_2026-01-28_16-29-01.jpg"
                )
                ,
                $keyboard
            );
    }



    private function stepCalcPages($userId, $text)
    {
        if ($text === "❌ Отмена") {
            $this->startCommand();
            return;
        }

        $pages = (int) filter_var($text, FILTER_SANITIZE_NUMBER_INT);

        if ($pages < 20) {
            BotManager::bot()->reply("⚠️ Минимальный объём книги — <b>20 страниц</b>.\nПожалуйста, введите количество страниц от 20 (кратное 4).");
            return;
        }

        // Число страниц должно быть кратно 4
        if ($pages % 4 !== 0) {
            $rounded = (int)(ceil($pages / 4) * 4);
            BotManager::bot()->reply("⚠️ Книги и журналы выпускаются только с числом страниц, кратным 4.\nБлижайшее подходящее значение: <b>{$rounded}</b>.\n\nВведите количество страниц (кратное 4):");
            return;
        }

        $calcData = ['pages' => $pages];
        Cache::put("bot_user_calc_data_{$userId}", $calcData, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_CALC_FORMAT, now()->addHours(2));

        $formatText = "📐 <b>Шаг 2 из 4: Формат книги</b>\n\nВыберите формат издания:";
        $keyboard = [
            [["text" => "📄 Формат А5 (148×210 мм)"]],
            [["text" => "📝 Формат А4 (210×297 мм)"]],
            [["text" => "❌ Отмена"]],
        ];
        BotManager::bot()->replyKeyboard($formatText, $keyboard);
    }

    private function stepCalcFormat($userId, $text)
    {
        if ($text === "❌ Отмена") {
            $this->startCommand();
            return;
        }

        $formats = [
            "📄 Формат А5 (148×210 мм)" => 'A5',
            "📝 Формат А4 (210×297 мм)" => 'A4',
        ];

        if (!isset($formats[$text])) {
            BotManager::bot()->reply("Пожалуйста, выберите формат из меню.");
            return;
        }

        $calcData = Cache::get("bot_user_calc_data_{$userId}", []);
        $calcData['format'] = $formats[$text];
        $calcData['format_label'] = $text;
        Cache::put("bot_user_calc_data_{$userId}", $calcData, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_CALC_SOURCE, now()->addHours(2));

        $sourceText = "📝 <b>Шаг 3 из 4: Исходные материалы</b>\n\nЕсть ли у вас черновики, дневники, записи?";
        $keyboard = [
            [["text" => "📂 Да, есть материалы"]],
            [["text" => "🆕 Нет, пишем с нуля"]],
            [["text" => "❌ Отмена"]],
        ];
        BotManager::bot()->replyKeyboard($sourceText, $keyboard);
    }

    private function stepCalcSource($userId, $text)
    {
        if ($text === "❌ Отмена") { $this->startCommand(); return; }

        $calcData = Cache::get("bot_user_calc_data_{$userId}", []);
        $calcData['source'] = $text;
        Cache::put("bot_user_calc_data_{$userId}", $calcData, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_CALC_PHOTOS, now()->addHours(2));

        $keyboard = [
            [["text" => "🚫 Без фото"]],
            [["text" => "📸 До 20 фото"]],
            [["text" => "📸 20-40 фото"]],
            [["text" => "📸 40-60 фото"]],
            [["text" => "❌ Отмена"]],
        ];
        BotManager::bot()->replyKeyboard("🖼 <b>Шаг 4 из 5: Фотографии</b>\n\nСколько фотографий планируется в книге?\n<i>(Обработка и вставка: 1 000 ₽ за каждые 20 шт.)</i>", $keyboard);
    }

    private function stepCalcPhotos($userId, $text)
    {
        if ($text === "❌ Отмена") { $this->startCommand(); return; }

        $photos = 0;
        if ($text === "📸 До 20 фото") $photos = 20;
        if ($text === "📸 20-40 фото") $photos = 40;
        if ($text === "📸 40-60 фото") $photos = 60;

        $calcData = Cache::get("bot_user_calc_data_{$userId}", []);
        $calcData['photos'] = $photos;
        Cache::put("bot_user_calc_data_{$userId}", $calcData, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_CALC_PRINT, now()->addHours(2));

        $printText = "🖨 <b>Шаг 5 из 5: Тип издания</b>\n\nВыберите, что нужно:";
        $keyboard = [
            [["text" => "💻 Только электронная версия (PDF)"]],
            [["text" => "⬛ Печать ч/б + электронная версия"]],
            [["text" => "🎨 Цветная печать + электронная версия"]],
            [["text" => "❌ Отмена"]],
        ];
        BotManager::bot()->replyKeyboard($printText, $keyboard);
    }

    private function stepCalcPrint($userId, $text)
    {
        if ($text === "❌ Отмена") {
            $this->startCommand();
            return;
        }

        $calcData = Cache::get("bot_user_calc_data_{$userId}", []);
        $calcData['print'] = $text;

        $pages  = (int)($calcData['pages'] ?? 0);
        $format = $calcData['format'] ?? 'A5';

        // 150₽ (A5) или 225₽ (A4) за страницу (написание)
        $basePricePerPage = ($format === 'A4') ? 225 : 150;
        $writingPrice = $pages * $basePricePerPage;

        $photoCount = (int)($calcData['photos'] ?? 0);
        $photoPrice = (int)(ceil($photoCount / 20) * 1000);
        $coverPrice = 500;
        $ePrice = 0; 

        // --- Печать ---
        $printPrice = 0;
        $bindingPrice = 0;
        $printLabel = '';
        switch ($text) {
            case "💻 Только электронная версия (PDF)":
                $printLabel = "Электронная версия (PDF)";
                break;
            case "⬛ Печать ч/б + электронная версия":
                $printPrice = ($pages / 20) * 800;
                $bindingPrice = 700;        // твёрдый переплёт
                $printLabel = "Ч/б печать + твёрдый переплёт";
                break;
            case "🎨 Цветная печать + электронная версия":
                $printPrice = ($pages / 20) * 1200;
                $bindingPrice = 700;
                $printLabel = "Цветная печать + твёрдый переплёт";
                break;
            default:
                BotManager::bot()->reply("Пожалуйста, выберите вариант из меню.");
                return;
        }

        $totalMin = $writingPrice + $ePrice + $printPrice + $bindingPrice + $coverPrice + $photoPrice;
        $totalMax = (int)($totalMin * 1.15); // вилка +15%

        $photoCount = (int)($calcData['photos'] ?? 0);
        $photoPrice = (int)(ceil($photoCount / 20) * 1000);
        $coverPrice = 500;

        $writingFmt = number_format($writingPrice, 0, '.', ' ');
        $totalMinFmt = number_format($totalMin, 0, '.', ' ');
        $totalMaxFmt = number_format($totalMax, 0, '.', ' ');

        $breakdown = "🔸 Обложка: <b>500 ₽</b>\n";
        if ($photoPrice > 0) {
            $photoPriceFmt = number_format($photoPrice, 0, '.', ' ');
            $breakdown .= "🔸 Фото ({$photoCount} шт.): <b>{$photoPriceFmt} ₽</b>\n";
        }
        
        if ($printPrice > 0) {
            $printFmt   = number_format($printPrice, 0, '.', ' ');
            $bindingFmt = number_format($bindingPrice, 0, '.', ' ');
            $breakdown .= "🔸 Печать: <b>{$printFmt} ₽</b>\n" .
                          "🔸 Переплёт: <b>{$bindingFmt} ₽</b>\n";
        }

        $resultText = "💰 <b>Предварительный расчёт стоимости:</b>\n\n" .
                      "📄 Объём: <b>{$pages} стр.</b> · Формат: <b>{$format}</b>\n" .
                      "📝 Материалы: <b>{$calcData['source']}</b>\n" .
                      "🖨 Издание: <b>{$printLabel}</b>\n";

        if ($photoCount > 0) {
            $resultText .= "🖼 Фотографии: <b>{$photoCount} шт.</b>\n";
        }
        
        $resultText .= "➖➖➖➖➖➖➖\n" .
                      "✏️ Написание/Редактура текста: <b>{$writingFmt} ₽</b>\n" .
                      $breakdown .
                      "➖➖➖➖➖➖➖\n" .
                      "📊 <b>Итого: от {$totalMinFmt} до {$totalMaxFmt} ₽</b>\n\n" .
                      "<i>❗ Предварительная оценка. Точная стоимость уточняется после обсуждения.</i>";

        $keyboard = [
            [
                ["text" => "✅ Зафиксировать и оставить заявку", "callback_data" => "fix_price"],
            ],
            [
                ["text" => "🔄 Рассчитать заново", "callback_data" => "calc_retry"],
            ]
        ];

        $calcResults = [
            'pages'  => $pages,
            'format' => $format,
            'source' => $calcData['source'],
            'print'  => $printLabel,
            'range'  => "от {$totalMinFmt} до {$totalMaxFmt} ₽"
        ];
        
        if ($photoCount > 0) {
            $calcResults['photos'] = $photoCount;
        }

        Cache::put("bot_user_calc_results_{$userId}", $calcResults, now()->addHours(2));

        Cache::forget("bot_user_state_{$userId}");
        Cache::forget("bot_user_calc_data_{$userId}");

        BotManager::bot()->replyKeyboard("Расчёт готов! 🎉", null);
        BotManager::bot()->replyInlineKeyboard($resultText, $keyboard);
    }

    public function quizCommand()
    {
        $userId = BotManager::bot()->currentBotUser()->id;
        Cache::put("bot_user_state_{$userId}", self::STATE_QUIZ_Q1, now()->addHours(2));
        Cache::forget("bot_user_quiz_data_{$userId}");

        $text = "📝 <b>Тест: Какой жанр вам подходит?</b>\n\nОтветьте на 5 вопросов, чтобы мы могли подобрать идеальный формат для вашей будущей книги.\n\n<b>Вопрос 1 из 5:</b>\nКакова основная цель вашей книги?";
        
        $keyboard = [
            [
                ["text" => "🌟 Самовыражение и личная история"],
            ],
            [
                ["text" => "👨‍👩‍👧 Сохранение истории для детей и внуков"],
            ],
            [
                ["text" => "🏢 Укрепление репутации бизнеса"],
            ],
            [
                ["text" => "🎓 Передача профессионального опыта"],
            ],
            [
                ["text" => "❌ Отмена"],
            ]
        ];

        BotManager::bot()->replyKeyboard($text, $keyboard);
    }

    private function stepQuizQ1($userId, $text)
    {
        if ($text === "❌ Отмена") { $this->startCommand(); return; }

        $scores = [
            'memoirs' => 0, 'family' => 0, 'corp' => 0, 'expert' => 0, 'bio' => 0
        ];
        
        $context = 'memoirs';

        switch ($text) {
            case "🌟 Самовыражение и личная история": 
                $scores['memoirs'] += 2; $scores['bio'] += 1; 
                $context = 'memoirs';
                break;
            case "👨‍👩‍👧 Сохранение истории для детей и внуков": 
                $scores['family'] += 2; 
                $context = 'family';
                break;
            case "🏢 Укрепление репутации бизнеса": 
                $scores['corp'] += 2; 
                $context = 'business';
                break;
            case "🎓 Передача профессионального опыта": 
                $scores['expert'] += 2; 
                $context = 'expert';
                break;
            default:
                BotManager::bot()->reply("Пожалуйста, выберите вариант из меню.");
                return;
        }

        Cache::put("bot_user_quiz_data_{$userId}", $scores, now()->addHours(2));
        Cache::put("bot_user_quiz_context_{$userId}", $context, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_QUIZ_Q2, now()->addHours(2));

        $text = "<b>Вопрос 2 из 5:</b>\nКто является главным героем произведения?";
        
        $keyboards = [
            'memoirs' => [
                [["text" => "👤 Я сам(а)"]],
                [["text" => "👵 Моя семья и окружение"]],
                [["text" => "❌ Отмена"]],
            ],
            'family' => [
                [["text" => "👤 Я сам(а)"]],
                [["text" => "👵 Мои предки и семья"]],
                [["text" => "❌ Отмена"]],
            ],
            'business' => [
                [["text" => "👤 Я (основатель)"]],
                [["text" => "👥 Наша компания или команда"]],
                [["text" => "❌ Отмена"]],
            ],
            'expert' => [
                [["text" => "👤 Я (как эксперт)"]],
                [["text" => "💡 Моя методика и кейсы"]],
                [["text" => "❌ Отмена"]],
            ]
        ];

        $keyboard = $keyboards[$context] ?? $keyboards['memoirs'];
        BotManager::bot()->replyKeyboard($text, $keyboard);
    }

    private function stepQuizQ2($userId, $text)
    {
        if ($text === "❌ Отмена") { $this->startCommand(); return; }
        $scores = Cache::get("bot_user_quiz_data_{$userId}", []);
        $context = Cache::get("bot_user_quiz_context_{$userId}", 'memoirs');
        
        switch ($text) {
            case "👤 Я сам(а)": $scores['memoirs'] += 2; $scores['bio'] += 1; break;
            case "👵 Мои предки и семья": $scores['family'] += 2; break;
            case "👵 Моя семья и окружение": $scores['memoirs'] += 1; $scores['family'] += 1; break;
            case "👥 Наша компания или команда": $scores['corp'] += 2; break;
            case "👤 Я (основатель)": $scores['corp'] += 1; $scores['bio'] += 1; break;
            case "💡 Моя методика и кейсы": $scores['expert'] += 2; break;
            case "👤 Я (как эксперт)": $scores['expert'] += 1; $scores['bio'] += 1; break;
        }

        Cache::put("bot_user_quiz_data_{$userId}", $scores, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_QUIZ_Q3, now()->addHours(2));

        $text = "<b>Вопрос 3 из 5:</b>\nВ каком стиле вы бы хотели видеть текст?";
        
        $keyboards = [
            'memoirs' => [
                [["text" => "🎭 Эмоциональный и художественный"]],
                [["text" => "🧘 Философский / Размышления"]],
                [["text" => "❌ Отмена"]],
            ],
            'family' => [
                [["text" => "📜 Хроника / Летопись"]],
                [["text" => "🎭 Эмоциональный и художественный"]],
                [["text" => "❌ Отмена"]],
            ],
            'business' => [
                [["text" => "🚀 История успеха (Success Story)"]],
                [["text" => "📊 Деловой / Фактический"]],
                [["text" => "❌ Отмена"]],
            ],
            'expert' => [
                [["text" => "📖 Практическое руководство"]],
                [["text" => "🗣 Живой диалог с читателем"]],
                [["text" => "❌ Отмена"]],
            ]
        ];

        $keyboard = $keyboards[$context] ?? $keyboards['memoirs'];
        BotManager::bot()->replyKeyboard($text, $keyboard);
    }

    private function stepQuizQ3($userId, $text)
    {
        if ($text === "❌ Отмена") { $this->startCommand(); return; }
        $scores = Cache::get("bot_user_quiz_data_{$userId}", []);
        $context = Cache::get("bot_user_quiz_context_{$userId}", 'memoirs');

        switch ($text) {
            case "🎭 Эмоциональный и художественный": $scores['memoirs'] += 1; $scores['bio'] += 2; $scores['family'] += 1; break;
            case "📜 Хроника / Летопись": $scores['family'] += 2; $scores['corp'] += 1; break;
            
            case "📊 Деловой / Фактический": $scores['business'] += 1; $scores['expert'] += 1; break;
            case "🚀 История успеха (Success Story)": $scores['corp'] += 2; $scores['bio'] += 1; break;
            
            case "📖 Практическое руководство": $scores['expert'] += 2; break;
            case "🗣 Живой диалог с читателем": $scores['expert'] += 1; $scores['memoirs'] += 1; break;
            case "🧘 Философский / Размышления": $scores['memoirs'] += 2; break;
        }

        Cache::put("bot_user_quiz_data_{$userId}", $scores, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_QUIZ_Q4, now()->addHours(2));

        $text = "<b>Вопрос 4 из 5:</b>\nКакие исходные материалы у вас уже есть?";
        
        $keyboards = [
            'memoirs' => [
                [["text" => "📓 Личные дневники и фото"]],
                [["text" => "🧠 Только воспоминания"]],
                [["text" => "❌ Отмена"]],
            ],
            'family' => [
                [["text" => "📜 Архивные документы"]],
                [["text" => "🗣 Рассказы родственников"]],
                [["text" => "❌ Отмена"]],
            ],
            'business' => [
                [["text" => "📉 Отчеты и презентации"]],
                [["text" => "🧠 Интервью с командой"]],
                [["text" => "❌ Отмена"]],
            ],
            'expert' => [
                [["text" => "📚 Статьи и лекции"]],
                [["text" => "🧠 Авторские наработки"]],
                [["text" => "❌ Отмена"]],
            ]
        ];

        $keyboard = $keyboards[$context] ?? $keyboards['memoirs'];
        BotManager::bot()->replyKeyboard($text, $keyboard);
    }

    private function stepQuizQ4($userId, $text)
    {
        if ($text === "❌ Отмена") { $this->startCommand(); return; }
        $scores = Cache::get("bot_user_quiz_data_{$userId}", []);
        $context = Cache::get("bot_user_quiz_context_{$userId}", 'memoirs');

        switch ($text) {
            case "📓 Личные дневники и фото": $scores['memoirs'] += 2; break;
            case "🧠 Только воспоминания": $scores['memoirs'] += 1; $scores['bio'] += 1; break;
            
            case "📜 Архивные документы": $scores['family'] += 2; break;
            case "🗣 Рассказы родственников": $scores['family'] += 1; $scores['memoirs'] += 1; break;
            
            case "📉 Отчеты и презентации": $scores['corp'] += 2; break;
            case "🧠 Интервью с командой": $scores['corp'] += 1; $scores['bio'] += 1; break;
            
            case "📚 Статьи и лекции": $scores['expert'] += 2; break;
            case "🧠 Авторские наработки": $scores['expert'] += 1; break;
        }

        Cache::put("bot_user_quiz_data_{$userId}", $scores, now()->addHours(2));
        Cache::put("bot_user_state_{$userId}", self::STATE_QUIZ_Q5, now()->addHours(2));

        $text = "<b>Вопрос 5 из 5:</b>\nДля кого пишется эта книга (целевая аудитория)?";
        
        $keyboards = [
            'memoirs' => [
                [["text" => "🌍 Широкий круг читателей"]],
                [["text" => "🏠 Близкие и друзья"]],
                [["text" => "❌ Отмена"]],
            ],
            'family' => [
                [["text" => "🏠 Только семья и потомки"]],
                [["text" => "🎁 Подарок близким"]],
                [["text" => "❌ Отмена"]],
            ],
            'business' => [
                [["text" => "🤝 Партнеры и клиенты"]],
                [["text" => "🌍 Рынок и конкуренты"]],
                [["text" => "❌ Отмена"]],
            ],
            'expert' => [
                [["text" => "👨‍🎓 Ученики и коллеги"]],
                [["text" => "💼 Потенциальные клиенты"]],
                [["text" => "❌ Отмена"]],
            ]
        ];

        $keyboard = $keyboards[$context] ?? $keyboards['memoirs'];
        BotManager::bot()->replyKeyboard($text, $keyboard);
    }

    private function stepQuizQ5($userId, $text)
    {
        if ($text === "❌ Отмена") { $this->startCommand(); return; }
        $scores = Cache::get("bot_user_quiz_data_{$userId}", []);

        switch ($text) {
            case "🏠 Близкие и друзья": $scores['memoirs'] += 2; break;
            case "🏠 Только семья и потомки": $scores['family'] += 2; break;
            case "🎁 Подарок близким": $scores['family'] += 1; $scores['memoirs'] += 1; break;
            
            case "🤝 Партнеры и клиенты": $scores['corp'] += 2; break;
            case "🌍 Рынок и конкуренты": $scores['corp'] += 1; $scores['bio'] += 1; break;
            
            case "🌍 Широкий круг читателей": $scores['memoirs'] += 1; $scores['bio'] += 2; break;
            
            case "👨‍🎓 Ученики и коллеги": $scores['expert'] += 2; break;
            case "💼 Потенциальные клиенты": $scores['expert'] += 1; $scores['corp'] += 1; break;
        }

        // Определяем победителя
        arsort($scores);
        $winner = key($scores);

        $results = [
            'memoirs' => [
                'title' => "Мемуары в стиле нон-фикшн",
                'desc' => "Ваша история — это глубокое погружение в личный опыт. Идеально подойдет формат живого повествования, где личные события переплетаются с мыслями и выводами."
            ],
            'family' => [
                'title' => "Семейная летопись (Хроника)",
                'desc' => "Для вас важнее всего преемственность поколений. Мы рекомендуем формат хроники, объединяющий архивные данные, рассказы о предках и фамильные легенды."
            ],
            'corp' => [
                'title' => "Корпоративная легенда",
                'desc' => "Ваша история тянет на бизнес-бестселлер. Книга о становлении компании, трудностях и победах, которая станет мощным инструментом репутации."
            ],
            'expert' => [
                'title' => "Книга эксперта / Руководство",
                'desc' => "Ваш опыт бесценен для других. Вам подойдет формат полезной книги, где кейсы и личные истории подкрепляют вашу методику работы."
            ],
            'bio' => [
                'title' => "Художественная биография",
                'desc' => "Ваша жизнь напоминает роман. Мы рекомендуем написать биографию с элементами художественного стиля, чтобы сделать чтение максимально захватывающим."
            ]
        ];

        $recommendation = $results[$winner];

        $resultText = "🏆 <b>Результат теста:</b>\n\nВам идеально подходит жанр:\n✨ <b>{$recommendation['title']}</b>\n\n{$recommendation['desc']}\n\nХотите обсудить детали или сразу закрепить за собой этот жанр?";

        $keyboard = [
            [
                ["text" => "🚀 Оставить заявку в этом жанре", "callback_data" => "quiz_order:{$winner}"],
            ],
            [
                ["text" => "🔁 Пройти тест заново", "callback_data" => "quiz_retry"],
            ]
        ];

        Cache::forget("bot_user_state_{$userId}");
        Cache::forget("bot_user_quiz_data_{$userId}");

        BotManager::bot()->replyKeyboard("Тест завершен!", null);
        BotManager::bot()->replyInlineKeyboard($resultText, $keyboard);
    }

    private function startOrderWithGenre($genreKey)
    {
        $userId = BotManager::bot()->currentBotUser()->id;
        $orderData = Cache::get("bot_user_order_data_{$userId}", []);
        
        // Маппинг ключей теста на названия услуг для заявки
        $titles = [
            'memoirs' => "Мемуары в стиле нон-фикшн",
            'family' => "Семейная летопись (Хроника)",
            'corp' => "Корпоративная легенда",
            'expert' => "Книга эксперта / Руководство",
            'bio' => "Художественная биография"
        ];
        
        $serviceName = $titles[$genreKey] ?? 'Индивидуальный жанр (из теста)';

        // 1. Сохраняем выбранный сервис
        $orderData['service'] = $serviceName;
        Cache::put("bot_user_order_data_{$userId}", $orderData, now()->addHours(2));
        
        $this->suggestGenerator($userId, $serviceName);
    }

    public function generatorCommand()
    {
        $userId = BotManager::bot()->currentBotUser()->id;
        Cache::put("bot_user_state_{$userId}", self::STATE_GEN_TOPIC, now()->addHours(1));

        $text = "✨ <b>Генератор первых строк</b>\n\nНе знаете, с чего начать? Напишите тему или ключевое событие вашей истории (например: <i>«Детство в деревне»</i>, <i>«Как я открыл первый бизнес»</i> или <i>«История моей семьи»</i>).\n\nЯ подберу для вас три литературных варианта начала книги!";
        
        $keyboard = [
            [
                ["text" => "❌ Отмена"],
            ]
        ];

        BotManager::bot()->replyKeyboard($text, $keyboard);
    }

    private function stepGenTopic($userId, $text)
    {
        if ($text === "❌ Отмена") {
            $this->startCommand();
            return;
        }

        if (empty($text) || strlen($text) < 3) {
            BotManager::bot()->reply("Пожалуйста, опишите тему чуть подробнее (хотя бы пару слов).");
            return;
        }

        BotManager::bot()->replyAction();
        
        $topic = $text;
        Cache::put("bot_user_gen_topic_{$userId}", $topic, now()->addHours(1));

        try {
            $apiKey = env('OPENROUTER_API_KEY');
            
            $url = "https://openrouter.ai/api/v1/chat/completions";

            $systemPrompt = "Ты — профессиональный литературный редактор и писатель. 
            Твоя задача: на основе темы пользователя сгенерировать ТРИ потрясающих, захватывающих варианта первой строки (зачина) для книги.
            Варианты должны быть в разных стилях:
            1. Личный/Мемуарный (ностальгия, искренность).
            2. Драматичный/Нуар (напряжение, интрига).
            3. Философский/Поэтичный (глубокие мысли, красивые метафоры).
            
            Отвечай строго на русском языке. 
            ВАЖНО: Каждый вариант начинай строго с метки 'Вариант 1:', 'Вариант 2:' и 'Вариант 3:'.
            Формат ответа: 
            Вариант 1: [текст]
            Вариант 2: [текст]
            Вариант 3: [текст]
            
            Не добавляй лишних приветствий, пояснений и вступительных фраз.";

            $response = Http::withHeaders([
                'Authorization' => "Bearer $apiKey",
                'HTTP-Referer' => env('APP_URL'), // Обязательно для OpenRouter
                'X-Title' => 'BioBook Bot',
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, [
                'model' => 'google/gemini-2.0-flash-001', // Используем одну из лучших и быстрых моделей на OpenRouter
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => "Моя тема: {$topic}"
                    ]
                ],
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                
                // Парсим варианты
                $variants = [];
                preg_match('/Вариант 1: (.*?)(?=Вариант 2|$)/s', $content, $m1);
                preg_match('/Вариант 2: (.*?)(?=Вариант 3|$)/s', $content, $m2);
                preg_match('/Вариант 3: (.*?)$/s', $content, $m3);
                
                $variants[1] = trim($m1[1] ?? 'Ошибка генерации варианта 1');
                $variants[2] = trim($m2[1] ?? 'Ошибка генерации варианта 2');
                $variants[3] = trim($m3[1] ?? 'Ошибка генерации варианта 3');
                
                Cache::put("bot_user_gen_variants_{$userId}", $variants, now()->addHours(1));

                $output = "✨ <b>Вот варианты начала для вашей книги по теме «{$topic}»:</b>\n\n";
                $output .= "📖 <b>1. Личный стиль:</b>\n<i>{$variants[1]}</i>\n\n";
                $output .= "🎭 <b>2. Драматичный стиль:</b>\n<i>{$variants[2]}</i>\n\n";
                $output .= "🧘 <b>3. Философский стиль:</b>\n<i>{$variants[3]}</i>\n\n";
                $output .= "Какой из них вам ближе? Выбор варианта сразу определит стиль вашей будущей книги!";
            } else {
                Log::error("OpenRouter API Error: " . $response->body());
                throw new \Exception("Ошибка API");
            }

        } catch (\Exception $e) {
            Log::error("Generator Error: " . $e->getMessage());
            $fallbackLine = "«У каждого из нас есть момент, когда время замирает. Для меня таким моментом стало " . mb_strtolower($topic) . "...»";
            $output = "⚠️ <b>Упс! Наш литературный ИИ временно взял творческий перерыв.</b>\n\nНо вот классический вариант для вашей темы:\n<i>{$fallbackLine}</i>";
            
            Cache::put("bot_user_gen_variants_{$userId}", [1 => $fallbackLine], now()->addHours(1));
        }

        $keyboard = [
            [
                ["text" => "✅ Выбрать вариант 1", "callback_data" => "gen_select:1"],
            ],
            [
                ["text" => "✅ Выбрать вариант 2", "callback_data" => "gen_select:2"],
            ],
            [
                ["text" => "✅ Выбрать вариант 3", "callback_data" => "gen_select:3"],
            ],
            [
                ["text" => "✨ Попробовать другую тему", "callback_data" => "gen_retry"],
            ],
            [
                ["text" => "🏠 В главное меню", "callback_data" => "gen_home"],
            ]
        ];

        Cache::forget("bot_user_state_{$userId}");
        BotManager::bot()->replyKeyboard("Генерация завершена!", null);
        BotManager::bot()->replyInlineKeyboard($output, $keyboard);
    }
}

