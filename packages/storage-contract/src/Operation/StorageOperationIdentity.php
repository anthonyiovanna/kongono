<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Operation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Internal\ContractGuard;

/** Carries caller-supplied correlation evidence without operation authority. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageOperationIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::storageOperationIdentity($value);
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
