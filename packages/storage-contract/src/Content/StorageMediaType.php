<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Content;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Internal\ContractGuard;

/** Carries canonical media-type evidence without sniffing or parameters. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageMediaType
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::mediaType($value);
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
