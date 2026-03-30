<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PushService
{
    /**
     * Send a batch of push notifications to multiple notifiables.
     * 
     * @param iterable $notifiables
     * @param string $title
     * @param string $message
     * @param string|null $url
     * @return array
     */
    public static function sendBatch($notifiables, $title, $message, $url = null)
    {
        $allSubscriptions = [];
        
        foreach ($notifiables as $notifiable) {
            $subs = $notifiable->routeNotificationFor('webpush');
            if (!$subs) continue;
            
            foreach ($subs as $sub) {
                $allSubscriptions[] = [
                    'endpoint' => $sub->endpoint,
                    'keys' => [
                        'p256dh' => $sub->public_key,
                        'auth' => $sub->auth_token
                    ]
                ];
            }
        }

        if (empty($allSubscriptions)) {
            return ['status' => 'no_subscriptions'];
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $message,
            'icon' => '/pwa-icon.png',
            'badge' => '/pwa-icon.png',
            'data' => [
                'url' => $url ?? route('home')
            ]
        ], JSON_UNESCAPED_UNICODE);

        $config = [
            'vapid_public' => config('services.vapid.public_key'),
            'vapid_private' => config('services.vapid.private_key'),
            'payload' => $payload,
            'subscriptions' => $allSubscriptions
        ];

        $tmpPath = storage_path('push_batch_' . uniqid() . '.json');
        file_put_contents($tmpPath, json_encode($config));

        $senderPath = base_path('push-sender.cjs');
        $output = shell_exec("node \"$senderPath\" \"$tmpPath\" 2>&1");
        
        Log::info("Push Batch Result: " . $output);
        
        if (file_exists($tmpPath)) unlink($tmpPath);

        return ['status' => 'ok', 'output' => $output];
    }
}
