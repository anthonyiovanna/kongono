<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Credential;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Internal\ContractGuard;

/** Identifies one operation-scoped lease without carrying credential material. */
#[Api(ApiStatus::Experimental)]
final readonly class CredentialLeaseIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'credential_lease_identity_grammar');
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
