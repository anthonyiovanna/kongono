<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Codec;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Internal\ContractGuard;

/** Identifies an inert application-selected codec without loading or executing it. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheCodecIdentity
{
    private string $name;
    private int $version;

    public function __construct(string $name, int $version)
    {
        $this->name = ContractGuard::codecName($name);
        $this->version = ContractGuard::codecVersion($version);
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
