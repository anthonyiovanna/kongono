<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Exception\InvalidStorageProfileException;

/** Carries lowercase SHA-256 evidence for one complete storage-profile set. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageProfileSetIdentity
{
    public function __construct(private string $value)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidStorageProfileException::invalid('source_identity');
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
