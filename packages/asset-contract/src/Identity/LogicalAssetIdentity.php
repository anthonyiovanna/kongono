<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Internal\ContractGuard;

/** Names a logical asset without interpreting the value as a path or URL. */
#[Api(ApiStatus::Experimental)]
final readonly class LogicalAssetIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::logicalIdentity($value);
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
