<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Exception\InvalidCacheProfileArtifactException;

/** Carries lowercase SHA-256 evidence for exact canonical artifact bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheProfileArtifactIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidCacheProfileArtifactException::invalid('artifact_identity');
        }
    }

    public static function fromBytes(string $bytes): self
    {
        return new self(hash('sha256', $bytes));
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
