<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Promemoria push di una scadenza. Sincrona di proposito: su Render non gira
 * un queue worker.
 */
class DeadlineReminder extends Notification
{
    public function __construct(
        public readonly int $deadlineId,
        public readonly string $title,
        public readonly string $body,
    ) {}

    /**
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            // Stesso tag per la stessa scadenza: il sollecito sostituisce il precedente.
            ->tag('deadline-'.$this->deadlineId)
            ->icon('/pwa-192x192.png')
            ->data(['url' => '/deadlines?deadline='.$this->deadlineId]);
    }
}
