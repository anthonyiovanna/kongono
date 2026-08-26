<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;

/** Carries one opaque caller-defined optimistic-concurrency token. */
#[Api(ApiStatus::Experimental)]
final readonly class ConcurrencyToken
{
    public function __construct(
        private string $scheme,
        private string $value,
    ) {
        if (1 !== preg_match('/^[a-z][a-z0-9._-]{0,63}$/D', $scheme)) {
            throw InvalidPersistenceValueException::validation();
        }

        if (
            '' === $value
            || strlen($value) > 512
            || 1 !== preg_match('//u', $value)
            || 1 === preg_match('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u', $value)
        ) {
            throw InvalidPersistenceValueException::validation();
        }
    }

    public function scheme(): string
    {
        return $this->scheme;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->scheme === $other->scheme && $this->value === $other->value;
    }
}
