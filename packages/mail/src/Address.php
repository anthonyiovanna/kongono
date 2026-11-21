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
        if (false === filter_var($trimmed, FILTER_VALIDATE_EMAIL) || str_contains($trimmed, "\r") || str_contains($trimmed, "\n")) {
            throw new MailException(sprintf('Invalid email address "%s".', $email));
        }
        $this->email = $trimmed;

        if (null !== $name) {
            $cleanName = trim(str_replace(["\r", "\n"], '', $name));
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

        return sprintf('"%s" <%s>', str_replace('"', '\\"', $this->name), $this->email);
    }
}
