<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetResolution\Internal\ResolutionGuard;

/** Carries the lowercase SHA-256 identity of exact canonical resolution bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetResolutionArtifactIdentity
{
    public function __construct(private string $value)
    {
        ResolutionGuard::sha256($value, 'artifact_identity_grammar');
    }

    public static function fromArtifactBytes(string $bytes): self
    {
        return new self(hash('sha256', $bytes));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }
}
