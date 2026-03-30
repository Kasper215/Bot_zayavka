<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Models\DeviceNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceNotificationController extends Controller
{
    /**
     * Регистрирует устройство и возвращает его токен.
     * POST /api/device/register
     */
    public function register(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:36', // UUID
        ]);

        $device = DeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => $request->user()?->id,
                'user_agent' => $request->userAgent(),
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'device_id' => $device->id]);
    }

    /**
     * Возвращает непрочитанные уведомления для устройства.
     * GET /api/device/poll?token=xxx
     */
    public function poll(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json(['notifications' => []]);
        }

        $device = DeviceToken::where('token', $token)->first();

        if (!$device) {
            return response()->json(['notifications' => []]);
        }

        // Обновляем last_seen_at
        $device->update(['last_seen_at' => now()]);

        // Возвращаем непрочитанные
        $notifications = $device->pendingNotifications()
            ->select(['id', 'title', 'body', 'url', 'icon', 'created_at'])
            ->get();

        // Отмечаем как прочитанные
        if ($notifications->isNotEmpty()) {
            $device->pendingNotifications()->update(['read_at' => now()]);
        }

        return response()->json(['notifications' => $notifications]);
    }

    /**
     * Отмечает уведомление прочитанным.
     * POST /api/device/read
     */
    public function markRead(Request $request)
    {
        $request->validate(['token' => 'required', 'notification_id' => 'required|integer']);

        DeviceNotification::whereHas('deviceToken', fn($q) => $q->where('token', $request->token))
            ->where('id', $request->notification_id)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
