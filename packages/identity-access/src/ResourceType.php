<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\Exception\InvalidIdentityAccessValueException;

/** Names one application-owned resource category without class lookup. */
#[Api(ApiStatus::Experimental)]
final readonly class ResourceType
{
    public function __construct(private string $value)
    {
        if (
            '' === $value
            || strlen($value) > 128
            || 1 !== preg_match('/^[a-z][a-z0-9]*(?:[._:-][a-z0-9]+)*$/D', $value)
        ) {
            throw InvalidIdentityAccessValueException::resource();
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
