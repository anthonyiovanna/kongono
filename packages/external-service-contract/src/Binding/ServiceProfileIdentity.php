<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Binding;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Internal\ContractGuard;

/** Carries one application-owned service-profile identity as inert evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class ServiceProfileIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'service_profile_identity_grammar');
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
