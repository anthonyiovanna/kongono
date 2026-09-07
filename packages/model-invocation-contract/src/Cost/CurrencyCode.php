<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Cost;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Internal\ContractGuard;

/** Carries an exact three-letter currency label without conversion semantics. */
#[Api(ApiStatus::Experimental)]
final readonly class CurrencyCode
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::currencyCode($value);
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
