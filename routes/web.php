<?php


use App\Http\Controllers\UserController;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::any('/register-webhook', [\App\Http\Controllers\TelegramController::class, "registerWebhooks"]);
Route::post('/webhook', [\App\Http\Controllers\TelegramController::class, "handler"]);
Route::get("/bot", [\App\Http\Controllers\TelegramController::class, "homePage"]);
Route::get("/blocked", [\App\Http\Controllers\TelegramController::class, "blockedPage"])
    ->name("blocked");

// Единая точка входа для админки
Route::get('/admin', function(Request $request) {
    if ($request->user()->role == 0) {
        return redirect()->route('bot.home');
    }
    return redirect()->route('admin.dashboard');
})->middleware('auth');

// Авторизация Mini App для админки (получение данных)
Route::match(['get', 'post'], '/admin-auth', function(Request $request) {
    if ($request->isMethod('post') && $request->has('initData')) {
        $queryStr = $request->input('initData');
        
        $utilities = new class { use \App\Http\Middleware\Service\Utilities; };
        
        if (!$queryStr || !$utilities->validateTGData($queryStr)) {
            \Illuminate\Support\Facades\Log::error("TG Auth failed. Data: " . $queryStr);
            return response("Ошибка авторизации или подпись не верна. Откройте приложение через Telegram.", 403);
        }

        $tgData = [];
        parse_str($queryStr, $tgData);
        
        if(!isset($tgData['user'])) {
            return response("Нет данных о пользователе Telegram.", 403);
        }

        $tgUser = json_decode($tgData['user'], true);
        
        $user = User::query()->where('telegram_chat_id', $tgUser['id'])->first();
        
        if (!$user || $user->role == 0) {
            return response("У вас нет прав администратора.", 403);
        }
        
        Auth::login($user);
        
        // Серверный редирект на дашборд после успешной формы
        return redirect()->route('admin.dashboard');
    }

    return view('bot-login');
});



Route::get('/', function() {
    return redirect('/admin');
});

Route::get('/bot-info', [\App\Http\Controllers\TelegramController::class, "homePage"])->name('bot.home');

// Удобная ссылка для выхода
Route::get('/logout', function(Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout.get');

// Админ-панель для заявок
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'tg.role:manager']) // Минимальная роль для доступа
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/leads', [\App\Http\Controllers\Admin\LeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/export', [\App\Http\Controllers\Admin\LeadController::class, 'export'])->name('leads.export');
        Route::put('/leads/{lead}', [\App\Http\Controllers\Admin\LeadController::class, 'update'])->name('leads.update');
        Route::delete('/leads/destroy-all', [\App\Http\Controllers\Admin\LeadController::class, 'destroyAll'])->name('leads.destroy-all');
        
        // Управление сотрудниками
        Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [\App\Http\Controllers\Admin\UserController::class, 'updateRole'])->name('users.update-role');
        
        // Рассылка
        Route::get('/broadcast', [\App\Http\Controllers\Admin\BroadcastController::class, 'index'])->name('broadcast.index');
        Route::post('/broadcast', [\App\Http\Controllers\Admin\BroadcastController::class, 'send'])->name('broadcast.send');

        // Настройка команд бота
        Route::get('/setup-commands', function() {
            $commands = [
                ['command' => 'start', 'description' => 'Запустить бота / Главное меню'],
                ['command' => 'portfolio', 'description' => 'Посмотреть портфолио'],
                ['command' => 'faq', 'description' => 'Часто задаваемые вопросы'],
                ['command' => 'contacts', 'description' => 'Связь с менеджером'],
            ];
            
            $bot = \App\Facades\BotMethods::bot();
            $bot->setMyCommands($commands);
            
            // Описание бота (то, что видно до нажатия Старт)
            $description = "👋 Добро пожаловать в BioBook!\nЗдесь вы можете оставить заявку на написание книги. Будь то биография, мемуары или корпоративная история — мы поможем превратить вашу идею в готовый текст.\n\n👇 Нажмите СТАРТ, чтобы заполнить короткую анкету.";
            $bot->setMyDescription($description);
            
            // Короткое описание (то, что видно в профиле бота)
            $shortDescription = "Ваш персональный гид в мире историй и биографий. Оформите заявку на книгу прямо здесь!";
            $bot->setMyShortDescription($shortDescription);

            // Устанавливаем кнопку меню
            $bot->setChatMenuButton([
                'type' => 'commands'
            ]);
            
            return "Настройки бота успешно обновлены (команды, описание и кнопка меню)!";
        })->name('setup.commands');
    });

Route::prefix("bot-api")
    ->middleware(["tg.auth"])
    ->group(function () {


        Route::post('/users/self', [\App\Http\Controllers\TelegramController::class, "getSelf"]);

        Route::prefix('users')
            ->middleware(["tg.role:user"])
            ->group(function () {
                // Список всех пользователей
                Route::post('/send-video', [UserController::class, 'sendVideo']);
                Route::post('/send-form', [UserController::class, 'sendForm']);
                // Создать нового пользователя

            });
    });

require __DIR__.'/auth.php';



