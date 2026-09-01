<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Receipt;

use Kongono\AgentPrincipalContract\Internal\ContractGuard;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Holds one bounded receipt identity without loading or storing a receipt. */
#[Api(ApiStatus::Experimental)]
final readonly class ReceiptIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'receipt_identity_grammar');
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
