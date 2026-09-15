<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Internal\AdapterContractGuard;

/** Carries one bounded tool-neutral adapter profile identity. */
#[Api(ApiStatus::Experimental)]
final readonly class AdapterProfileIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = AdapterContractGuard::profile($value);
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
