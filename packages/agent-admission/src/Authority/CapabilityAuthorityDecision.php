<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Authority;

use Kongono\AgentAdmission\Exception\InvalidAgentAdmissionException;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authorization\AuthorizationDecisionReceipt;
use Kongono\IdentityAccess\AuthorizationDecision;

/** Records one inert capability reconciliation outcome and bounded receipt. */
#[Api(ApiStatus::Experimental)]
final readonly class CapabilityAuthorityDecision
{
    public function __construct(
        private CapabilityIdentity $capabilityIdentity,
        private ?CapabilityRequirement $declaredRequirement,
        private CapabilityAuthorityStatus $status,
        private ?AuthorizationDecisionReceipt $authorizationReceipt,
    ) {
        $valid = match ($status) {
            CapabilityAuthorityStatus::Permitted => null !== $declaredRequirement
                && null !== $authorizationReceipt
                && AuthorizationDecision::Permit === $authorizationReceipt->decision(),
            CapabilityAuthorityStatus::NotRequested, CapabilityAuthorityStatus::MissingAuthorization => null !== $declaredRequirement
                && null === $authorizationReceipt,
            CapabilityAuthorityStatus::Denied => null !== $declaredRequirement
                && null !== $authorizationReceipt
                && AuthorizationDecision::Deny === $authorizationReceipt->decision(),
            CapabilityAuthorityStatus::Indeterminate => null !== $declaredRequirement
                && null !== $authorizationReceipt
                && AuthorizationDecision::Indeterminate === $authorizationReceipt->decision(),
            CapabilityAuthorityStatus::Undeclared => null === $declaredRequirement,
        };
        if (!$valid) {
            throw InvalidAgentAdmissionException::invalid('capability_decision_shape');
        }
    }

    public function capabilityIdentity(): CapabilityIdentity
    {
        return $this->capabilityIdentity;
    }

    public function declaredRequirement(): ?CapabilityRequirement
    {
        return $this->declaredRequirement;
    }

    public function status(): CapabilityAuthorityStatus
    {
        return $this->status;
    }

    public function authorizationReceipt(): ?AuthorizationDecisionReceipt
    {
        return $this->authorizationReceipt;
    }

    public function permitted(): bool
    {
        return CapabilityAuthorityStatus::Permitted === $this->status;
    }
}
