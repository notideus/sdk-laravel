<?php

declare(strict_types=1);

namespace Notideus\Laravel\Notifications;

use Illuminate\Notifications\Notification;
use Notideus\NotideusClient;

final class NotideusChannel
{
    public function __construct(private readonly NotideusClient $notideus)
    {
    }

    public function send(mixed $notifiable, Notification $notification): void
    {
        $recipient = null;
        if (is_object($notifiable) && method_exists($notifiable, 'routeNotificationFor')) {
            $recipient = $notifiable->routeNotificationFor('notideus')
                ?? $notifiable->routeNotificationFor('mail');
        }

        if (!is_string($recipient) || $recipient === '') {
            $recipient = is_object($notifiable) && property_exists($notifiable, 'email')
                ? $notifiable->email
                : null;
        }

        if (!is_string($recipient) || $recipient === '') {
            return;
        }

        if (!method_exists($notification, 'toNotideus')) {
            return;
        }

        /** @var array<string, mixed> $params */
        $params = $notification->toNotideus($notifiable);
        $params['to'] = $params['to'] ?? [$recipient];

        $this->notideus->emails()->send($params);
    }
}
