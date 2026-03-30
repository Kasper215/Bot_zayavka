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
        // Вместо стандартного канала, вызываем наш Node.js скрипт-мостик
        $this->sendViaNode($notifiable);
        
        // Возвращаем пустой массив, чтобы Laravel не пытался отправить через WebPushChannel (он всё равно сломан)
        return [];
    }

    protected function sendViaNode($notifiable)
    {
        // 1. Собираем подписки пользователя
        $subscriptions = $notifiable->routeNotificationFor('webpush');
        if (!$subscriptions || count($subscriptions) == 0) return;

        // 2. Параметры VAPID из .env
        $publicKey = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');

        // 3. Собираем полезную нагрузку
        $payload = json_encode([
            'title' => '🚨 НОВАЯ ЗАЯВКА!',
            'body' => "Клиент: {$this->lead->client_name}\nУслуга: {$this->lead->service_type}",
            'icon' => '/pwa-icon.png',
            'data' => [
                'id' => $this->lead->id,
                'url' => route('admin.leads.index')
            ]
        ], JSON_UNESCAPED_UNICODE);

        // 4. Отправляем каждую подписку через Node.js через временный конфиг
        $senderPath = base_path('push-sender.cjs');
        
        foreach ($subscriptions as $sub) {
            // Создаем УНИКАЛЬНЫЙ временный конфиг для этой подписки
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
            
            $tmpFile = 'push_config_' . uniqid() . '.json';
            $tmpPath = storage_path($tmpFile);
            file_put_contents($tmpPath, json_encode($config));

            // Запускаем мост. Указываем полный путь к node, если он в PATH
            // 6. Выполняем скрипт
            $output = shell_exec("node \"$senderPath\" \"$tmpPath\" 2>&1");
            \Illuminate\Support\Facades\Log::info("Push Node Execution [" . $sub->endpoint . "]: " . $output);
            
            // 7. УДАЛЯЕМ временный файл за собой!
            if (file_exists($tmpPath)) unlink($tmpPath);
        }
    }
}
