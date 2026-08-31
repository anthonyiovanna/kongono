<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Version;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Internal\ContractGuard;

/** Holds one exact opaque extension version without interpreting compatibility. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionVersion
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::extensionVersion($value);
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
