<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Operation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Internal\ContractGuard;

/** Names one service operation without describing or authorizing its effect. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceOperationIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'operation_identity_grammar');
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
