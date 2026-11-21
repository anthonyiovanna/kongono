<?php

declare(strict_types=1);

namespace Kongono\Mail;

use Kongono\Core\Notifications\Notification;
use Kongono\Core\Notifications\NotificationChannelInterface;

/** Delivers Kongono Notifications as transactional emails via Mailer. */
final readonly class MailNotificationChannel implements NotificationChannelInterface
{
    public function __construct(
        private Mailer $mailer,
        private string $channelName = 'mail',
    ) {}

    public function name(): string
    {
        return $this->channelName;
    }

    public function send(array $recipient, Notification $notification): void
    {
        $address = is_string($recipient['email'] ?? null) ? trim($recipient['email']) : '';
        if ('' === $address) {
            throw new MailException('MailNotificationChannel requires recipient["email"].');
        }
        $name = is_string($recipient['name'] ?? null) ? $recipient['name'] : null;

        $email = Email::create()
            ->to($address, $name)
            ->subject($notification->subject)
            ->text($notification->body);

        $this->mailer->send($email);
    }
}
