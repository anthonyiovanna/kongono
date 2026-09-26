<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Content;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Internal\ContractGuard;

/** Carries one finite object byte count. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageByteCount
{
    private int $value;

    public function __construct(int $value)
    {
        $this->value = ContractGuard::byteCount($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
