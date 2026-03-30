<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use Illuminate\Support\Facades\Log;

class GeneralBroadcastNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $url;

    public function __construct($title, $message, $url = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url ?? route('home');
    }

    public function via($notifiable)
    {
        // Используем мост через Node.js, так как прямой канал помечен разработчиками как нестабильный
        $this->sendViaNode($notifiable);
        return [];
    }

    protected function sendViaNode($notifiable)
    {
        $subscriptions = $notifiable->routeNotificationFor('webpush');
        if (!$subscriptions || count($subscriptions) == 0) return;

        $publicKey = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');

        $payload = json_encode([
            'title' => $this->title,
            'body' => $this->message,
            'icon' => '/pwa-icon.png',
            'badge' => '/pwa-icon.png',
            'data' => [
                'url' => $this->url
            ]
        ], JSON_UNESCAPED_UNICODE);

        $senderPath = base_path('push-sender.cjs');
        
        foreach ($subscriptions as $sub) {
            $config = [
                'vapid_public' => $publicKey,
                'vapid_private' => $privateKey,
                'payload' => $payload,
                'subscription' => [
                    'endpoint' => $sub->endpoint,
                    'keys' => [
                        'p256dh' => $sub->public_key,
                        'auth' => $sub->auth_token
                    ]
                ]
            ];
            
            $tmpPath = storage_path('push_broadcast_' . uniqid() . '.json');
            file_put_contents($tmpPath, json_encode($config));

            $output = shell_exec("node \"$senderPath\" \"$tmpPath\" 2>&1");
            Log::info("Broadcast Push Node Response [" . $sub->endpoint . "]: " . $output);
            
            if (file_exists($tmpPath)) unlink($tmpPath);
        }
    }
}
