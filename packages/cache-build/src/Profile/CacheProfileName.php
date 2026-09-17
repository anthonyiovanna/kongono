<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Exception\InvalidCacheProfileException;

/** Names one application-owned cache profile without granting authority. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheProfileName
{
    public function __construct(private string $value)
    {
        if (strlen($value) < 1 || strlen($value) > 64 || 1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $value)) {
            throw InvalidCacheProfileException::invalid('profile_name');
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
