<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Internal\ContractGuard;

/** Identifies one render operation without granting execution authority. */
#[Api(ApiStatus::Experimental)]
final readonly class RenderOperationIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'render_operation_identity_grammar');
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
