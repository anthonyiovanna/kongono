<?php

declare(strict_types=1);

namespace Kongono\Mail;

/** Immutable email address value object with strict header-injection protection. */
final readonly class Address
{
    public string $email;
    public ?string $name;

    public function __construct(string $email, ?string $name = null)
    {
        $trimmed = trim($email);
        if (false === filter_var($trimmed, FILTER_VALIDATE_EMAIL) || 1 === preg_match('/[\x00-\x1f\x7f]/', $trimmed)) {
            throw new MailException(sprintf('Invalid email address "%s".', $email));
        }
        $this->email = $trimmed;

        if (null !== $name) {
            $cleanName = trim((string) preg_replace('/[\x00-\x1f\x7f]/', '', $name));
            $this->name = '' !== $cleanName ? $cleanName : null;
        } else {
            $this->name = null;
        }
    }

    public function format(): string
    {
        if (null === $this->name) {
            return $this->email;
        }

        return sprintf('"%s" <%s>', str_replace(['\\', '"'], ['\\\\', '\\"'], $this->name), $this->email);
    }
}
