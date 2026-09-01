<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Identity;

use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;

/** Wraps one Agent-kind Principal reference without granting authority. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentPrincipalReference
{
    public function __construct(private PrincipalReference $reference)
    {
        if (PrincipalKind::Agent !== $reference->kind()) {
            throw InvalidAgentPrincipalContractException::invalidPrincipal('principal_kind');
        }
    }

    public function reference(): PrincipalReference
    {
        return $this->reference;
    }

    public function equals(self $other): bool
    {
        return $this->reference->equals($other->reference);
    }
}
