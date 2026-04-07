<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Lead;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class LeadStatusNotification extends Notification
{
    use Queueable;

    protected $lead;
    protected $statusLabel;

    /**
     * Create a new notification instance.
     */
    public function __construct(Lead $lead, string $statusLabel)
    {
        $this->lead = $lead;
        $this->statusLabel = $statusLabel;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'lead_id' => $this->lead->id,
            'title' => 'Статус вашего заказа обновлен!',
            'message' => "Ваш заказ на тему '{$this->lead->service_type}' теперь в статусе: {$this->statusLabel}",
            'url' => route('account.index'),
        ];
    }

    /**
     * Get the web push representation of the notification.
     *
     * @param  mixed  $notifiable
     * @param  mixed  $notification
     * @return \NotificationChannels\WebPush\WebPushMessage
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('BioBook: обновление статуса')
            ->icon('/icons/icon-192x192.png')
            ->body("Статус вашего заказа: {$this->statusLabel}")
            ->action('Посмотреть в ЛК', 'view_account')
            ->options(['tag' => 'lead-status-' . $this->lead->id]);
    }
}
