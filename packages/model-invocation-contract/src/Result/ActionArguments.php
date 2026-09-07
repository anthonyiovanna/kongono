<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Internal\ContractGuard;

/** Carries inert bounded JSON-object text without executing or canonicalizing it. */
#[Api(ApiStatus::Experimental)]
final readonly class ActionArguments
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::actionArguments($value);
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
