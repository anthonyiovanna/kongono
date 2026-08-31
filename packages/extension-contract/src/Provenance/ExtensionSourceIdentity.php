<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Provenance;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Internal\ContractGuard;

/** Holds one exact opaque lowercase SHA-256 source identity. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionSourceIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::extensionSourceIdentity($value);
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
