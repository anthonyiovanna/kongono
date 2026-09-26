<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageAdapterContract\Internal\AdapterContractGuard;

/** Carries bounded adapter identity without provider or location semantics. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageAdapterIdentity
{
    private string $name;
    private int $revision;

    public function __construct(string $name, int $revision)
    {
        $this->name = AdapterContractGuard::identity($name);
        $this->revision = AdapterContractGuard::revision($revision);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function revision(): int
    {
        return $this->revision;
    }

    public function canonical(): string
    {
        return $this->name . '@' . $this->revision;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->revision === $other->revision;
    }
}
