<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Cost;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Internal\ContractGuard;

/** Preserves an exact bounded nonnegative decimal representation. */
#[Api(ApiStatus::Experimental)]
final readonly class DecimalAmount
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::decimalAmount($value);
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
