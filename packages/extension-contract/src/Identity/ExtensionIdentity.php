<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Internal\ContractGuard;

/** Holds one exact vendor/name extension identity without asserting admission. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::extensionIdentity($value);
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
