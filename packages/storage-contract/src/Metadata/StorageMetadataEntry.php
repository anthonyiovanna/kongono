<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Metadata;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Internal\ContractGuard;

/** Carries one bounded caller-owned metadata entry. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageMetadataEntry
{
    private string $name;
    private string $value;

    public function __construct(string $name, string $value)
    {
        $this->name = ContractGuard::metadataName($name);
        $this->value = ContractGuard::metadataValue($value);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->value === $other->value;
    }
}
