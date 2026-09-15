<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Exception\InvalidAssetBuildArtifactException;

/** Carries the lowercase SHA-256 identity of exact canonical artifact bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetBuildIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidAssetBuildArtifactException::invalid('identity_grammar');
        }
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
        return $this->value === $other->value;
    }
}
