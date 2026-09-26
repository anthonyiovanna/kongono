<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Version;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Internal\ContractGuard;

/** Carries opaque provider-scoped concurrency evidence without global ordering. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageObjectVersion
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::objectVersion($value);
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
