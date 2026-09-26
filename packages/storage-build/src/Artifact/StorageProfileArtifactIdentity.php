<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Exception\InvalidStorageProfileArtifactException;

/** Carries lowercase SHA-256 evidence for exact canonical artifact bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageProfileArtifactIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidStorageProfileArtifactException::invalid('artifact_identity');
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
