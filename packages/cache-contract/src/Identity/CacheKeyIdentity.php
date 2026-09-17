<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Internal\ContractGuard;

/** Carries domain-separated lowercase SHA-256 key evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheKeyIdentity
{
    private string $digest;

    public function __construct(string $digest)
    {
        $this->digest = ContractGuard::cacheKeyIdentity($digest);
    }

    public static function from(CacheNamespace $namespace, LogicalCacheKey $logicalKey): self
    {
        return new self(hash('sha256', "kongono.cache.key.v1\0" . $namespace->value() . "\0" . $logicalKey->value()));
    }

    public function digest(): string
    {
        return $this->digest;
    }

    public function equals(self $other): bool
    {
        return $this->digest === $other->digest;
    }
}
