<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Exception\InvalidCacheProfileException;

/** Carries one explicit maximum accepted cache-entry value size. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheProfileValueLimit
{
    public function __construct(private int $bytes)
    {
        if ($bytes < 1 || $bytes > 1_048_576) {
            throw InvalidCacheProfileException::invalid('value_limit', limit: 1_048_576);
        }
    }

    public function bytes(): int
    {
        return $this->bytes;
    }

    public function equals(self $other): bool
    {
        return $this->bytes === $other->bytes;
    }
}
