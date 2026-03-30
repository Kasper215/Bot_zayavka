<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DeviceToken;
use App\Models\DeviceNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BroadcastController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Доступ запрещен');
        }

        // Считаем всех подписчиков: WebPush + нативные устройства без FCM
        $webPushCount = User::has('pushSubscriptions')->count()
            + \App\Models\GuestSubscriber::has('pushSubscriptions')->count();

        $deviceCount = DeviceToken::whereDate('last_seen_at', '>=', now()->subDays(30))->count();

        return Inertia::render('Admin/Broadcast', [
            'pushSubscribersCount' => $webPushCount,
            'deviceSubscribersCount' => $deviceCount,
        ]);
    }

    public function send(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Доступ запрещен');
        }

        $request->validate([
            'title'   => 'required|string|max:100',
            'message' => 'required|string|min:5',
            'url'     => 'nullable|url',
        ]);

        $title   = $request->input('title');
        $message = $request->input('message');
        $url     = $request->input('url', route('home'));

        // ── 1. WebPush (через FCM, работает при наличии подписок) ────────────
        $users  = User::has('pushSubscriptions')->get();
        $guests = \App\Models\GuestSubscriber::has('pushSubscriptions')->get();
        $allNotifiables = $users->concat($guests);

        if ($allNotifiables->isNotEmpty()) {
            \App\Services\PushService::sendBatch($allNotifiables, $title, $message, $url);
        }

        // ── 2. Наш FCM-free polling (работает в России без VPN) ──────────────
        $activeDevices = DeviceToken::whereDate('last_seen_at', '>=', now()->subDays(30))->get();

        foreach ($activeDevices as $device) {
            DeviceNotification::create([
                'device_token_id' => $device->id,
                'title'           => $title,
                'body'            => $message,
                'url'             => $url,
                'icon'            => '/pwa-icon.png',
            ]);
        }

        $totalWeb    = $allNotifiables->count();
        $totalDevice = $activeDevices->count();
        $total       = max($totalWeb, $totalDevice); // не дублировать счёт для одного юзера

        if ($total === 0) {
            return back()->with('error', 'Нет активных подписчиков для рассылки.');
        }

        return back()->with('success', "Рассылка запущена! WebPush: {$totalWeb}, Native polling: {$totalDevice}.");
    }
}
