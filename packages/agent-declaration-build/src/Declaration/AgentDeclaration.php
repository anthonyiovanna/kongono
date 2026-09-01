<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Declaration;

use Kongono\AgentDeclarationBuild\Exception\InvalidAgentDeclarationException;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\AgentPrincipalContract\Provenance\AgentProvenance;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Aggregates one canonical inert declaration without admission or authority. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentDeclaration
{
    /** @var list<CapabilityRequest> */
    private array $capabilityRequests;
    /** @var list<AgentConstraint> */
    private array $constraints;
    /** @var list<EvidenceReference> */
    private array $evidenceReferences;

    /**
     * @param list<CapabilityRequest> $capabilityRequests
     * @param list<AgentConstraint> $constraints
     * @param list<EvidenceReference> $evidenceReferences
     */
    public function __construct(
        private AgentDeclarationIdentity $identity,
        private AgentPrincipalReference $principal,
        array $capabilityRequests,
        array $constraints,
        array $evidenceReferences,
        private AgentProvenance $provenance,
        private AgentVisibility $visibility,
    ) {
        self::validateCapabilityRequests($capabilityRequests);
        self::validateConstraints($constraints);
        self::validateEvidenceReferences($evidenceReferences);
        $this->capabilityRequests = [...$capabilityRequests];
        $this->constraints = [...$constraints];
        $this->evidenceReferences = [...$evidenceReferences];
    }

    public function identity(): AgentDeclarationIdentity
    {
        return $this->identity;
    }

    public function principal(): AgentPrincipalReference
    {
        return $this->principal;
    }

    /** @return list<CapabilityRequest> */
    public function capabilityRequests(): array
    {
        return [...$this->capabilityRequests];
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

    /** @param array<array-key, mixed> $requests */
    private static function validateCapabilityRequests(array $requests): void
    {
        self::validateList(
            $requests,
            32,
            CapabilityRequest::class,
            'capability_requests',
            static fn(CapabilityRequest $request): string => $request->identity()->value(),
        );
    }

    /** @param array<array-key, mixed> $constraints */
    private static function validateConstraints(array $constraints): void
    {
        self::validateList(
            $constraints,
            9,
            AgentConstraint::class,
            'constraints',
            static fn(AgentConstraint $constraint): string => $constraint->kind()->value,
        );
    }

    /** @param array<array-key, mixed> $references */
    private static function validateEvidenceReferences(array $references): void
    {
        self::validateList(
            $references,
            32,
            EvidenceReference::class,
            'evidence_references',
            static fn(EvidenceReference $reference): string => $reference->kind()->value . ':' . $reference->identity()->value(),
        );
    }

    /**
     * @template T of object
     * @param array<array-key, mixed> $values
     * @param class-string<T> $type
     * @param callable(T): string $key
     */
    private static function validateList(array $values, int $maximum, string $type, string $name, callable $key): void
    {
        if (!array_is_list($values)) {
            throw InvalidAgentDeclarationException::invalid($name . '_not_list', count($values));
        }
        if (count($values) > $maximum) {
            throw InvalidAgentDeclarationException::limit($name . '_count', count($values));
        }

        $previous = null;
        $seen = [];
        foreach ($values as $position => $value) {
            if (!$value instanceof $type) {
                throw InvalidAgentDeclarationException::invalid('invalid_' . $name . '_entry', count($values), $position);
            }
            $current = $key($value);
            if (isset($seen[$current])) {
                throw InvalidAgentDeclarationException::invalid('duplicate_' . $name . '_entry', count($values), $position);
            }
            if (null !== $previous && strcmp($previous, $current) >= 0) {
                throw InvalidAgentDeclarationException::invalid('noncanonical_' . $name . '_order', count($values), $position);
            }
            $seen[$current] = true;
            $previous = $current;
        }
    }
}
