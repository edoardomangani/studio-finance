<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TestPush extends Notification
{
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
            ->title('StudioFinance')
            ->body('Le notifiche funzionano su questo dispositivo.')
            ->tag('test')
            ->icon('/pwa-192x192.png')
            ->data(['url' => '/dashboard']);
    }
}
