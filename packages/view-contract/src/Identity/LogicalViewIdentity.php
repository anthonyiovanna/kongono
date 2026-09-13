<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Internal\ContractGuard;

/** Names a logical view without interpreting the value as a path. */
#[Api(ApiStatus::Experimental)]
final readonly class LogicalViewIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'logical_view_identity_grammar');
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
