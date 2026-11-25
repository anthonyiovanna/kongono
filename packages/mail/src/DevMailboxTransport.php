<?php

declare(strict_types=1);

namespace Kongono\Mail;

use Kongono\Core\Dev\DevMailboxInterface;
use Psr\Log\LoggerInterface;

/**
 * Local development mail transport that captures every sent email into Kongono's built-in
 * `/_kongono` Dev Mailbox (HTML preview, plain-text, headers, attachments) with zero SMTP or Docker.
 */
final readonly class DevMailboxTransport implements MailTransportInterface
{
    public function __construct(
        private DevMailboxInterface $mailbox,
        private ?LoggerInterface $logger = null,
    ) {}

    public function send(Email $email): string
    {
        $id = 'devmail_' . bin2hex(random_bytes(8));
        $to = array_map(static fn(Address $a): string => $a->format(), $email->to);
        $cc = array_map(static fn(Address $a): string => $a->format(), $email->cc);
        $bcc = array_map(static fn(Address $a): string => $a->format(), $email->bcc);
        $attachments = array_map(
            static fn(array $a): array => [
                'filename' => $a['filename'],
                'content_type' => $a['contentType'],
                'bytes' => strlen($a['bytes']),
            ],
            $email->attachments,
        );

        $this->mailbox->append([
            'id' => $id,
            'sent_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'from' => null !== $email->from ? $email->from->format() : '',
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
            'reply_to' => null !== $email->replyTo ? $email->replyTo->format() : null,
            'subject' => $email->subject,
            'html' => $email->htmlBody,
            'text' => $email->textBody,
            'attachments' => $attachments,
        ]);

        if (null !== $this->logger) {
            $this->logger->info('Outgoing email captured in Kongono Dev Mailbox', [
                'message_id' => $id,
                'to' => $to,
                'subject' => $email->subject,
            ]);
        }

        return $id;
    }
}
