<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Internal\ContractGuard;

/** Carries one canonical logical identity without path or URL semantics. */
#[Api(ApiStatus::Experimental)]
final readonly class LogicalObjectIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::logicalObjectIdentity($value);
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
