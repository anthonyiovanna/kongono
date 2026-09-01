<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Proposal;

use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Exception\InvalidAgentPrincipalContractException;
use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\AgentPrincipalContract\Intent\AgentIntent;
use Kongono\AgentPrincipalContract\Provenance\AgentProvenance;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Aggregates canonical inert proposal evidence without admission or execution. */
#[Api(ApiStatus::Experimental)]
final readonly class ActionProposal
{
    /** @var list<AgentConstraint> */
    private array $constraints;
    /** @var list<EvidenceReference> */
    private array $evidenceReferences;

    /**
     * @param list<AgentConstraint> $constraints
     * @param list<EvidenceReference> $evidenceReferences
     */
    public function __construct(
        private ProposalIdentity $identity,
        private AgentPrincipalReference $principal,
        private OperationReference $operation,
        private AgentIntent $intent,
        private CapabilityRequest $capabilityRequest,
        array $constraints,
        array $evidenceReferences,
        private AgentProvenance $provenance,
        private AgentVisibility $visibility,
    ) {
        self::validateConstraints($constraints);
        self::validateEvidenceReferences($evidenceReferences);
        $this->constraints = [...$constraints];
        $this->evidenceReferences = [...$evidenceReferences];
    }

    public function identity(): ProposalIdentity
    {
        return $this->identity;
    }

    public function principal(): AgentPrincipalReference
    {
        return $this->principal;
    }

    public function operation(): OperationReference
    {
        return $this->operation;
    }

    public function intent(): AgentIntent
    {
        return $this->intent;
    }

    public function capabilityRequest(): CapabilityRequest
    {
        return $this->capabilityRequest;
    }

    /** @return list<AgentConstraint> */
    public function constraints(): array
    {
        return [...$this->constraints];
    }

    /** @return list<EvidenceReference> */
    public function evidenceReferences(): array
    {
        return [...$this->evidenceReferences];
    }

    public function provenance(): AgentProvenance
    {
        return $this->provenance;
    }

    public function visibility(): AgentVisibility
    {
        return $this->visibility;
    }

    /** @param array<array-key, mixed> $constraints */
    private static function validateConstraints(array $constraints): void
    {
        if (!array_is_list($constraints)) {
            throw InvalidAgentPrincipalContractException::invalidProposal('constraints_not_list', count($constraints));
        }
        if (count($constraints) > 9) {
            throw InvalidAgentPrincipalContractException::contractLimitExceeded('constraint_count', count($constraints));
        }

        $previousKey = null;
        $seen = [];
        foreach ($constraints as $position => $constraint) {
            if (!$constraint instanceof AgentConstraint) {
                throw InvalidAgentPrincipalContractException::invalidProposal('invalid_constraint_entry', count($constraints), $position);
            }
            $key = $constraint->kind()->value;
            if (isset($seen[$key])) {
                throw InvalidAgentPrincipalContractException::invalidProposal('duplicate_constraint_kind', count($constraints), $position);
            }
            if (null !== $previousKey && strcmp($previousKey, $key) >= 0) {
                throw InvalidAgentPrincipalContractException::invalidProposal('non_canonical_constraint_order', count($constraints), $position);
            }
            $seen[$key] = true;
            $previousKey = $key;
        }
    }

    /** @param array<array-key, mixed> $references */
    private static function validateEvidenceReferences(array $references): void
    {
        if (!array_is_list($references)) {
            throw InvalidAgentPrincipalContractException::invalidProposal('evidence_not_list', count($references));
        }
        if (count($references) > 32) {
            throw InvalidAgentPrincipalContractException::contractLimitExceeded('evidence_count', count($references));
        }

        $previousKey = null;
        $seen = [];
        foreach ($references as $position => $reference) {
            if (!$reference instanceof EvidenceReference) {
                throw InvalidAgentPrincipalContractException::invalidProposal('invalid_evidence_entry', count($references), $position);
            }
            $key = $reference->kind()->value . ':' . $reference->identity()->value();
            if (isset($seen[$key])) {
                throw InvalidAgentPrincipalContractException::invalidProposal('duplicate_evidence_reference', count($references), $position);
            }
            if (null !== $previousKey && strcmp($previousKey, $key) >= 0) {
                throw InvalidAgentPrincipalContractException::invalidProposal('non_canonical_evidence_order', count($references), $position);
            }
            $seen[$key] = true;
            $previousKey = $key;
        }
    }
}
