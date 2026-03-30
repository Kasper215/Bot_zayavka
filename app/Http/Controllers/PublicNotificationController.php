<?php

namespace App\Http\Controllers;

use App\Models\GuestSubscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class PublicNotificationController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required',
            'keys.p256dh' => 'required',
            'keys.auth' => 'required',
        ]);

        try {
            $user = auth()->user();
            
            // Если есть залогиненный юзер (админ/менеджер) - подписываем его
            if ($user && $user instanceof User) {
                $user->updatePushSubscription(
                    $request->endpoint,
                    $request->input('keys.p256dh'),
                    $request->input('keys.auth')
                );
                return response()->json(['success' => true, 'type' => 'user']);
            }

            // Иначе - подписываем как гостя
            $sessionId = Session::getId();
            $guest = GuestSubscriber::firstOrCreate(
                ['session_id' => $sessionId],
                ['last_active_at' => now()]
            );

            $guest->updatePushSubscription(
                $request->endpoint,
                $request->input('keys.p256dh'),
                $request->input('keys.auth')
            );

            return response()->json(['success' => true, 'type' => 'guest']);
            
        } catch (\Exception $e) {
            Log::error("Public Push Subscription Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
