<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Exception\InvalidCacheProfileArtifactException;
use Kongono\CacheBuild\Internal\BuildGuard;
use Kongono\CacheBuild\Internal\CanonicalJson;
use Kongono\CacheBuild\Profile\CacheProfileSetIdentity;

/** Carries the deterministic identity of one fixed-profile build input. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheProfileBuildIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidCacheProfileArtifactException::invalid('build_identity');
        }
    }

    public static function fromSourceIdentity(CacheProfileSetIdentity $sourceIdentity): self
    {
        return new self(hash('sha256', CanonicalJson::encode([
            'profile' => BuildGuard::BUILD_PROFILE,
            'source_identity' => $sourceIdentity->value(),
        ])));
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
