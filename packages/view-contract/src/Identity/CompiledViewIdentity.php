<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Internal\ContractGuard;

/** Carries a content identity as evidence, never loading authority. */
#[Api(ApiStatus::Experimental)]
final readonly class CompiledViewIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::compiledIdentity($value);
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
