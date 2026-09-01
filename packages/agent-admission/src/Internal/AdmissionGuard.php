<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Internal;

use Kongono\AgentAdmission\Authority\CapabilityAuthorityDecision;
use Kongono\AgentAdmission\Evidence\CapabilityAuthorizationEvidence;
use Kongono\AgentAdmission\Exception\InvalidAgentAdmissionException;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Centralizes exact bounded list admission without sorting or coercion. */
#[Api(ApiStatus::Internal)]
final class AdmissionGuard
{
    /**
     * @param array<array-key, mixed> $values
     * @return list<CapabilityIdentity>
     */
    public static function requestedCapabilities(array $values): array
    {
        return self::orderedObjects($values, CapabilityIdentity::class, 32, 'requested_capability', 'requested_capabilities', static fn(CapabilityIdentity $value): string => $value->value());
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<CapabilityAuthorizationEvidence>
     */
    public static function authorizationEvidence(array $values): array
    {
        return self::orderedObjects($values, CapabilityAuthorizationEvidence::class, 32, 'authorization_evidence', 'authorization_evidence', static fn(CapabilityAuthorizationEvidence $value): string => $value->capabilityIdentity()->value());
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<CapabilityAuthorityDecision>
     */
    public static function capabilityDecisions(array $values): array
    {
        if (!array_is_list($values)) {
            throw InvalidAgentAdmissionException::invalid('capability_decisions_not_list', count($values));
        }
        if (count($values) > 64) {
            throw InvalidAgentAdmissionException::invalid('capability_decisions_count', count($values));
        }
        $accepted = [];
        $seen = [];
        foreach ($values as $position => $value) {
            if (!$value instanceof CapabilityAuthorityDecision) {
                throw InvalidAgentAdmissionException::invalid('invalid_capability_decision_entry', count($values), $position);
            }
            $key = $value->capabilityIdentity()->value();
            if (isset($seen[$key])) {
                throw InvalidAgentAdmissionException::invalid('duplicate_capability_decision', count($values), $position);
            }
            $seen[$key] = true;
            $accepted[] = $value;
        }

        return $accepted;
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<AgentConstraint>
     */
    public static function constraints(array $values): array
    {
        return self::orderedObjects($values, AgentConstraint::class, 9, 'constraint', 'constraints', static fn(AgentConstraint $value): string => $value->kind()->value);
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<EvidenceReference>
     */
    public static function evidenceReferences(array $values): array
    {
        return self::orderedObjects($values, EvidenceReference::class, 32, 'evidence_reference', 'evidence_references', static fn(EvidenceReference $value): string => $value->kind()->value . ':' . $value->identity()->value());
    }

    public static function declarationDigest(string $value): string
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidAgentAdmissionException::invalid('declaration_digest_grammar');
        }

        return $value;
    }

    /**
     * @template T of object
     * @param array<array-key, mixed> $values
     * @param class-string<T> $type
     * @param callable(T): string $key
     * @return list<T>
     */
    private static function orderedObjects(array $values, string $type, int $maximum, string $kind, string $plural, callable $key): array
    {
        if (!array_is_list($values)) {
            throw InvalidAgentAdmissionException::invalid($plural . '_not_list', count($values));
        }
        if (count($values) > $maximum) {
            throw InvalidAgentAdmissionException::invalid($plural . '_count', count($values));
        }

        $accepted = [];
        $seen = [];
        $previous = null;
        foreach ($values as $position => $value) {
            if (!$value instanceof $type) {
                throw InvalidAgentAdmissionException::invalid('invalid_' . $kind . '_entry', count($values), $position);
            }
            $current = $key($value);
            if (isset($seen[$current])) {
                throw InvalidAgentAdmissionException::invalid('duplicate_' . $kind, count($values), $position);
            }
            if (null !== $previous && strcmp($previous, $current) >= 0) {
                throw InvalidAgentAdmissionException::invalid('noncanonical_' . $kind . '_order', count($values), $position);
            }
            $seen[$current] = true;
            $previous = $current;
            $accepted[] = $value;
        }

        return $accepted;
    }

    private function __construct() {}
}
