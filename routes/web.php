<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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

Route::get('/', function () {
    return \Inertia\Inertia::render('MenuPage');
})->name('home');

Route::post('/leads/submit', [\App\Http\Controllers\PublicLeadController::class, 'submitForm'])
    ->name('leads.submit')
    ->middleware('throttle:5,1');

// Auth Routes (Login)
require __DIR__.'/auth.php';

// Admin Routes (Auth + Role Manager/Admin)
Route::group([
    'prefix' => 'admin',
    'middleware' => ['auth']
], function () {

    // Common routes for all staff
    Route::get('/', function () {
        return redirect()->route('admin.leads.index');
    });

    Route::get('/dashboard', function () {
        return redirect()->route('admin.leads.index');
    })->name('dashboard');

    // Subscription route for push
    Route::post('/notifications/subscribe', [\App\Http\Controllers\Admin\UserController::class, 'subscribeNotifications'])->name('notifications.subscribe');

    // Leads Management
    Route::group([
        'middleware' => ['tg.role:manager'],
        'as' => 'admin.'
    ], function () {
        Route::get('/leads', [\App\Http\Controllers\Admin\LeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/check-new', [\App\Http\Controllers\Admin\LeadController::class, 'checkNew'])->name('leads.check-new');
        Route::get('/leads/export', [\App\Http\Controllers\Admin\LeadController::class, 'export'])->name('leads.export');
        Route::get('/leads/{lead}/download/{filename}', [\App\Http\Controllers\Admin\LeadController::class, 'downloadFile'])->name('leads.download');
        Route::delete('/leads/{lead}/file/{filename}', [\App\Http\Controllers\Admin\LeadController::class, 'deleteFile'])->name('leads.delete-file');
        
        Route::patch('/leads/{lead}', [\App\Http\Controllers\Admin\LeadController::class, 'update'])->name('leads.update');
        Route::delete('/leads/{lead}', [\App\Http\Controllers\Admin\LeadController::class, 'destroy'])->name('leads.destroy');
        Route::delete('/leads/all/destroy', [\App\Http\Controllers\Admin\LeadController::class, 'destroyAll'])->name('leads.destroy-all');
    });

    // Users & Broadcast Management (Admin Only)
    Route::group([
        'middleware' => ['tg.role:admin']
    ], function () {
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->names('admin.users');
        Route::post('/users/{user}/toggle-role', [\App\Http\Controllers\Admin\UserController::class, 'toggleRole'])->name('admin.users.toggle-role');
        Route::post('/users/{user}/block', [\App\Http\Controllers\Admin\UserController::class, 'block'])->name('admin.users.block');
        Route::post('/users/{user}/unblock', [\App\Http\Controllers\Admin\UserController::class, 'unblock'])->name('admin.users.unblock');

        Route::get('/broadcast', [\App\Http\Controllers\Admin\BroadcastController::class, 'index'])->name('admin.broadcast.index');
        Route::post('/broadcast', [\App\Http\Controllers\Admin\BroadcastController::class, 'send'])->name('admin.broadcast.send');
    });
});

// Telegram Bot API Routes (Custom auth)
Route::group([
    'prefix' => 'bot-api',
    'middleware' => ['tg.auth']
], function () {
    Route::post('/users/self', [\App\Http\Controllers\TelegramController::class, "getSelf"]);
    
    Route::group([
        'prefix' => 'users',
        'middleware' => ['tg.role:user']
    ], function () {
        Route::get('/', [\App\Http\Controllers\TelegramController::class, "index"]);
        Route::get('/{user}', [\App\Http\Controllers\TelegramController::class, "show"]);
    });
});

// AI Generation Route
Route::post('/ai/generate', [\App\Http\Controllers\AIController::class, 'generate'])->name('ai.generate');
