<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Exception\InvalidStorageProfileException;

/** Names one application-owned storage profile without granting authority. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageProfileName
{
    public function __construct(private string $value)
    {
        if (strlen($value) < 1 || strlen($value) > 64 || 1 !== preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $value)) {
            throw InvalidStorageProfileException::invalid('profile_name');
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
