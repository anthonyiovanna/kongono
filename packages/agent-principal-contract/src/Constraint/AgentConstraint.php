<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Constraint;

use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one non-negative bound without metering, scheduling, or enforcement. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentConstraint
{
    public function __construct(
        private ConstraintKind $kind,
        private int $value,
    ) {
        if ($value < 0) {
            throw InvalidAgentPrincipalContractException::invalidProposal('constraint_value_range');
        }
    }

    public function kind(): ConstraintKind
    {
        return $this->kind;
    }

    public function value(): int
    {
        return $this->value;
    }
}
