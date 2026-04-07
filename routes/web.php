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

Route::get('/api/public/me', function () {
    return auth()->user();
});

// Personal Account Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/account', [\App\Http\Controllers\UserAccountController::class, 'index'])->name('account.index');
    Route::get('/api/leads/{lead}/status', [\App\Http\Controllers\UserAccountController::class, 'getLeadStatus'])->name('account.lead-status');
});

// Auth Routes (Login)
require __DIR__.'/auth.php';

// Public PWA & Push Routes
Route::post('/notifications/subscribe', [\App\Http\Controllers\PublicNotificationController::class, 'subscribe'])->name('notifications.subscribe');

// FCM-free Device Notification Routes (работает в России без VPN)
Route::post('/api/device/register', [\App\Http\Controllers\DeviceNotificationController::class, 'register']);
Route::get('/api/device/poll', [\App\Http\Controllers\DeviceNotificationController::class, 'poll']);
Route::post('/api/device/read', [\App\Http\Controllers\DeviceNotificationController::class, 'markRead']);

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

    // Leads Management
    Route::group([
        'middleware' => ['role:manager'],
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

        Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::patch('/payments/{payment}/status', [\App\Http\Controllers\Admin\PaymentController::class, 'updateStatus'])->name('payments.update-status');
        Route::post('/payments/settings', [\App\Http\Controllers\Admin\PaymentController::class, 'saveSettings'])->name('payments.save-settings');
    });

    // Users & Broadcast Management (Admin Only)
    Route::group([
        'middleware' => ['role:admin']
    ], function () {
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->names('admin.users');
        Route::post('/users/{user}/toggle-role', [\App\Http\Controllers\Admin\UserController::class, 'toggleRole'])->name('admin.users.toggle-role');
        Route::post('/users/{user}/block', [\App\Http\Controllers\Admin\UserController::class, 'block'])->name('admin.users.block');
        Route::post('/users/{user}/unblock', [\App\Http\Controllers\Admin\UserController::class, 'unblock'])->name('admin.users.unblock');

        Route::get('/broadcast', [\App\Http\Controllers\Admin\BroadcastController::class, 'index'])->name('admin.broadcast.index');
        Route::post('/broadcast', [\App\Http\Controllers\Admin\BroadcastController::class, 'send'])->name('admin.broadcast.send');
    });
});

// AI Generation Route
Route::post('/ai/generate-intro', [\App\Http\Controllers\AIController::class, 'generateIntro'])->name('ai.generate');

// Fallback for serving storage files if symlink is missing or broken on server
Route::get('/storage/{path}', function($path) {
    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        return response()->file(storage_path("app/public/{$path}"));
    }
    abort(404);
})->where('path', '.*');
