<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Exception\InvalidCacheProfileException;

/** Carries lowercase SHA-256 evidence for one complete cache-profile set. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheProfileSetIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidCacheProfileException::invalid('source_identity');
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
