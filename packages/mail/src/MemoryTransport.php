<?php

declare(strict_types=1);

namespace Kongono\Mail;

/** Deterministic in-memory mail transport for fast PHPUnit assertions. */
final class MemoryTransport implements MailTransportInterface
{
    /** @var list<Email> */
    private array $sent = [];

    public function send(Email $email): string
    {
        // Validate MIME serialization eagerly so malformed emails fail in tests too
        $email->toMimeString();
        $this->sent[] = $email;

        return 'mem_' . count($this->sent);
    }

    /** @return list<Email> */
    public function sent(): array
    {
        return $this->sent;
    }

    public function clear(): void
    {
        $this->sent = [];
    }
}
