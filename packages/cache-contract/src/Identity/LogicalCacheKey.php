<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Internal\ContractGuard;

/** Carries one logical cache key without path or provider interpretation. */
#[Api(ApiStatus::Experimental)]
final readonly class LogicalCacheKey
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::logicalCacheKey($value);
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
