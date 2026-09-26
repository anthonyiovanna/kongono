<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Pagination;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Internal\ContractGuard;

/** Carries opaque bounded pagination evidence that is never Safe diagnostic data. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageContinuationToken
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::continuationToken($value);
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
