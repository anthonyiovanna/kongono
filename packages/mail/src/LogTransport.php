<?php

declare(strict_types=1);

namespace Kongono\Mail;

use Psr\Log\LoggerInterface;

/** Logs outgoing emails via PSR-3 Logger without network I/O (ideal for local development). */
final readonly class LogTransport implements MailTransportInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function send(Email $email): string
    {
        $id = 'log_' . bin2hex(random_bytes(8));
        $this->logger->info('Outgoing email captured by LogTransport', [
            'message_id' => $id,
            'from' => null !== $email->from ? $email->from->email : '',
            'to' => array_map(static fn(Address $a): string => $a->email, $email->to),
            'subject' => $email->subject,
        ]);

        return $id;
    }
}
