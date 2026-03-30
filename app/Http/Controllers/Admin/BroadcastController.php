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

        // Считаем всех: и пользователей, и гостей-подписчиков
        $userCount = User::has('pushSubscriptions')->count();
        $guestCount = \App\Models\GuestSubscriber::has('pushSubscriptions')->count();
        
        return Inertia::render('Admin/Broadcast', [
            'pushSubscribersCount' => $userCount + $guestCount
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

        // Находим всех пользователей и гостей с подписками
        $users = User::has('pushSubscriptions')->get();
        $guests = \App\Models\GuestSubscriber::has('pushSubscriptions')->get();
        
        $totalCount = $users->count() + $guests->count();

        if ($totalCount === 0) {
            return back()->with('error', 'Нет активных подписчиков для рассылки.');
        }

        $notification = new GeneralBroadcastNotification($title, $message, $url);

        foreach ($users as $user) {
            $user->notify($notification);
        }

        foreach ($guests as $guest) {
            $guest->notify($notification);
        }

        return back()->with('success', "Рассылка запущена! Уведомления отправляются {$totalCount} пользователям.");
    }
}
