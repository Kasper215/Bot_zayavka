<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class NewLeadNotification extends Notification
{
    use Queueable;

    protected $lead;

    public function __construct($lead)
    {
        $this->lead = $lead;
    }

    public function via($notifiable)
    {
        // 1. Всегда пишем в локальную очередь уведомлений для поллинга (надежно в России)
        $this->sendViaPolling($notifiable);
        
        // 2. Дополнительно пробуем через Node/FCM (на случай VPN или если Google ожил)
        $this->sendViaNode($notifiable);
        
        return [];
    }

    protected function sendViaPolling($notifiable)
    {
        if (!$notifiable instanceof \App\Models\User) return;

        // Находим все устройства этого админа
        $devices = \App\Models\DeviceToken::where('user_id', $notifiable->id)->get();

        foreach ($devices as $device) {
            \App\Models\DeviceNotification::create([
                'device_token_id' => $device->id,
                'title' => '🚨 НОВАЯ ЗАЯВКА!',
                'body' => "Клиент: {$this->lead->client_name}\nУслуга: {$this->lead->service_type}",
                'url' => route('admin.leads.index'),
                'icon' => '/pwa-icon.png',
            ]);
        }
    }

    protected function sendViaNode($notifiable)
    {
        // Собираем подписки пользователя
        $subscriptions = $notifiable->routeNotificationFor('webpush');
        if (!$subscriptions || count($subscriptions) == 0) return;

        // Параметры VAPID
        $publicKey = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');

        $payload = json_encode([
            'title' => '🚨 НОВАЯ ЗАЯВКА!',
            'body' => "Клиент: {$this->lead->client_name}\nУслуга: {$this->lead->service_type}",
            'icon' => '/pwa-icon.png',
            'data' => [
                'id' => $this->lead->id,
                'url' => route('admin.leads.index')
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
            
            $tmpPath = storage_path('push_config_' . uniqid() . '.json');
            file_put_contents($tmpPath, json_encode($config));

            $output = shell_exec("node \"$senderPath\" \"$tmpPath\" 2>&1");
            \Illuminate\Support\Facades\Log::info("Push Fallback Output: " . $output);
            
            if (file_exists($tmpPath)) unlink($tmpPath);
        }
    }
}
