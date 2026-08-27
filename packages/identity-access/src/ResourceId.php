<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\Exception\InvalidIdentityAccessValueException;

/** Stores an exact opaque resource identifier without loading domain state. */
#[Api(ApiStatus::Experimental)]
final readonly class ResourceId
{
    public function __construct(private string $value)
    {
        if (!$this->isOpaqueValue($value)) {
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

    private function isOpaqueValue(string $value): bool
    {
        return '' !== $value
            && strlen($value) <= 512
            && 1 === preg_match('//u', $value)
            && 0 === preg_match('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u', $value);
    }
}
