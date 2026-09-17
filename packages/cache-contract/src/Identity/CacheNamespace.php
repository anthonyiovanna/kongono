<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Internal\ContractGuard;

/** Names one application-owned cache namespace without granting authority. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheNamespace
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::cacheNamespace($value);
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
