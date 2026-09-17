<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Internal\AdapterContractGuard;

/** Identifies one adapter implementation without exposing provider addressing. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheAdapterIdentity
{
    private string $name;
    private int $version;

    public function __construct(string $name, int $version)
    {
        $this->name = AdapterContractGuard::adapterName($name);
        $this->version = AdapterContractGuard::positiveVersion($version);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function canonical(): string
    {
        return $this->name . '@' . $this->version;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->version === $other->version;
    }
}
