<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Internal\ContractGuard;

/** Carries lowercase SHA-256 content evidence without granting authority. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheContentIdentity
{
    private string $digest;

    public function __construct(string $digest)
    {
        $this->digest = ContractGuard::cacheContentIdentity($digest);
    }

    public static function fromBytes(string $bytes): self
    {
        ContractGuard::cacheValueBytes($bytes);

        return new self(hash('sha256', $bytes));
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
