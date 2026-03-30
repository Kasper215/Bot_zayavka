<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\GeneralBroadcastNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Notification;

class BroadcastController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Доступ запрещен');
        }

        // Считаем именно тех, у кого есть активные push-подписки
        $userCount = User::has('pushSubscriptions')->count();
        
        return Inertia::render('Admin/Broadcast', [
            'pushSubscribersCount' => $userCount
        ]);
    }

    public function send(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Доступ запрещен');
        }

        $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string|min:5',
            'url' => 'nullable|url',
        ]);

        $title = $request->input('title');
        $message = $request->input('message');
        $url = $request->input('url', route('home'));

        // Находим всех пользователей с подписками
        $users = User::has('pushSubscriptions')->get();
        
        if ($users->isEmpty()) {
            return back()->with('error', 'Нет активных подписчиков для рассылки.');
        }

        foreach ($users as $user) {
            $user->notify(new GeneralBroadcastNotification($title, $message, $url));
        }

        return back()->with('success', "Рассылка запущена! Уведомления отправляются {$users->count()} пользователям.");
    }
}
