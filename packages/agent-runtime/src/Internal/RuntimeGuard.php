<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Internal;

use Kongono\AgentAdmission\Admission\AgentAdmissionDecision;
use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Constraint\ConstraintKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Proposal\ActionProposal;
use Kongono\AgentPrincipalContract\Receipt\AgentReceipt;
use Kongono\AgentPrincipalContract\Receipt\ReceiptOutcome;
use Kongono\AgentRuntime\Budget\AgentRuntimeBudget;
use Kongono\AgentRuntime\Capability\AgentCapabilityPort;
use Kongono\AgentRuntime\Exception\InvalidAgentRuntimeException;
use Kongono\AgentRuntime\Proposal\AgentProposalRequest;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Centralizes exact canonical validation without discovery, lookup, or external effects. */
#[Api(ApiStatus::Internal)]
final class RuntimeGuard
{
    public static function digest(string $value): string
    {
        if (64 !== strlen($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidAgentRuntimeException::invalid('digest_grammar');
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $ports
     * @return array{list<AgentCapabilityPort>, list<CapabilityIdentity>}
     */
    public static function capabilityPorts(array $ports, AgentDeclaration $declaration, AgentAuthorityPlan $plan): array
    {
        if (!array_is_list($ports)) {
            throw InvalidAgentRuntimeException::invalid('ports_not_list');
        }
        if (count($ports) > 32) {
            throw InvalidAgentRuntimeException::invalid('port_count');
        }

        $declared = [];
        foreach ($declaration->capabilityRequests() as $request) {
            $declared[$request->identity()->value()] = true;
        }
        $permitted = [];
        foreach ($plan->capabilityDecisions() as $decision) {
            if ($decision->permitted()) {
                $permitted[$decision->capabilityIdentity()->value()] = true;
            }
        }

        $validated = [];
        $identities = [];
        $previous = null;
        $seen = [];
        foreach ($ports as $port) {
            if (!$port instanceof AgentCapabilityPort) {
                throw InvalidAgentRuntimeException::invalid('invalid_port');
            }
            try {
                $identity = $port->capabilityIdentity();
            } catch (Throwable $failure) {
                throw InvalidAgentRuntimeException::invalid('port_identity_failure', $failure);
            }
            $key = $identity->value();
            if (isset($seen[$key])) {
                throw InvalidAgentRuntimeException::invalid('duplicate_port');
            }
            if (null !== $previous && strcmp($previous, $key) >= 0) {
                throw InvalidAgentRuntimeException::invalid('noncanonical_port_order');
            }
            if (!isset($declared[$key]) || !isset($permitted[$key])) {
                throw InvalidAgentRuntimeException::invalid('extraneous_port');
            }
            $seen[$key] = true;
            $previous = $key;
            $validated[] = $port;
            $identities[] = $identity;
        }

        return [$validated, $identities];
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<CapabilityIdentity>
     */
    public static function capabilityIdentities(array $values, string $name): array
    {
        return self::canonicalList($values, 32, CapabilityIdentity::class, static fn(CapabilityIdentity $value): string => $value->value(), $name);
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<AgentConstraint>
     */
    public static function constraints(array $values, string $name): array
    {
        return self::canonicalList($values, 9, AgentConstraint::class, static fn(AgentConstraint $value): string => $value->kind()->value, $name);
    }

    /**
     * @param array<array-key, mixed> $values
     * @return list<EvidenceReference>
     */
    public static function evidenceReferences(array $values, string $name): array
    {
        return self::canonicalList($values, 32, EvidenceReference::class, static fn(EvidenceReference $value): string => $value->kind()->value . ':' . $value->identity()->value(), $name);
    }

    /**
     * @param array<array-key, mixed> $receipts
     * @return list<AgentReceipt>
     */
    public static function receipts(array $receipts): array
    {
        if (!array_is_list($receipts)) {
            throw InvalidAgentRuntimeException::invalid('receipts_not_list');
        }
        if (count($receipts) > 6) {
            throw InvalidAgentRuntimeException::invalid('value_count');
        }
        $rank = [
            ReceiptOutcome::Proposed->value => 0,
            ReceiptOutcome::Authorized->value => 1,
            ReceiptOutcome::Attempted->value => 2,
            ReceiptOutcome::Performed->value => 3,
            ReceiptOutcome::Failed->value => 3,
            ReceiptOutcome::Cancelled->value => 4,
            ReceiptOutcome::Retired->value => 5,
        ];
        $validated = [];
        $previous = -1;
        foreach ($receipts as $receipt) {
            if (!$receipt instanceof AgentReceipt || !isset($rank[$receipt->outcome()->value])) {
                throw InvalidAgentRuntimeException::invalid('invalid_receipt');
            }
            $current = $rank[$receipt->outcome()->value];
            if ($current < $previous) {
                throw InvalidAgentRuntimeException::invalid('receipt_order');
            }
            $previous = $current;
            $validated[] = $receipt;
        }
        return $validated;
    }

    public static function validateBinding(AgentDeclaration $declaration, AgentAuthorityPlan $plan, AgentRuntimeBudget $budget): void
    {
        if (AgentAdmissionDecision::Admitted !== $plan->admissionDecision()) {
            throw InvalidAgentRuntimeException::invalid('plan_not_admitted');
        }
        try {
            $artifact = (new AgentDeclarationArtifactBuilder())->build($declaration);
        } catch (Throwable $failure) {
            throw InvalidAgentRuntimeException::invalid('declaration_rebuild_failed', $failure);
        }
        if ($artifact->declarationIdentity()->value() !== $plan->declarationIdentity()->value()) {
            throw InvalidAgentRuntimeException::invalid('declaration_identity_mismatch');
        }
        if ($artifact->declarationSha256() !== $plan->declarationSha256()) {
            throw InvalidAgentRuntimeException::invalid('declaration_digest_mismatch');
        }
        if (!$declaration->principal()->equals($plan->principal())) {
            throw InvalidAgentRuntimeException::invalid('principal_mismatch');
        }
        if (!self::sameConstraints($declaration->constraints(), $plan->constraints())) {
            throw InvalidAgentRuntimeException::invalid('constraints_mismatch');
        }
        if (!self::sameEvidence($declaration->evidenceReferences(), $plan->evidenceReferences())) {
            throw InvalidAgentRuntimeException::invalid('evidence_mismatch');
        }
        if (!self::sameCapabilityPlan($declaration, $plan)) {
            throw InvalidAgentRuntimeException::invalid('capability_plan_mismatch');
        }
        self::validateBudget($budget, $declaration->constraints());
    }

    /** @return list<CapabilityIdentity> */
    public static function permittedCapabilities(AgentAuthorityPlan $plan): array
    {
        $values = [];
        foreach ($plan->capabilityDecisions() as $decision) {
            if ($decision->permitted()) {
                $values[] = $decision->capabilityIdentity();
            }
        }
        return $values;
    }

    public static function validateProposal(ActionProposal $proposal, AgentDeclaration $declaration, AgentAuthorityPlan $plan, AgentProposalRequest $request): CapabilityIdentity
    {
        $intentMatches = $proposal->intent()->identity()->equals($request->intent()->identity())
            && $proposal->intent()->purpose()->equals($request->intent()->purpose());
        if (!$proposal->principal()->equals($request->principal()) || !$proposal->operation()->equals($request->operation()) || !$intentMatches) {
            throw InvalidAgentRuntimeException::invalid('proposal_mismatch');
        }
        if (!self::sameConstraints($proposal->constraints(), $request->constraints()) || !self::sameEvidence($proposal->evidenceReferences(), $request->evidenceReferences())) {
            throw InvalidAgentRuntimeException::invalid('proposal_mismatch');
        }
        if ($proposal->provenance()->source() !== $declaration->provenance()->source()
            || $proposal->provenance()->sourceIdentity() !== $declaration->provenance()->sourceIdentity()
            || $proposal->provenance()->sha256() !== $declaration->provenance()->sha256()
            || $proposal->visibility() !== $declaration->visibility()) {
            throw InvalidAgentRuntimeException::invalid('proposal_mismatch');
        }

        $capability = $proposal->capabilityRequest();
        $declared = null;
        foreach ($declaration->capabilityRequests() as $candidate) {
            if ($candidate->identity()->equals($capability->identity())) {
                $declared = $candidate;
                break;
            }
        }
        if (!$declared instanceof CapabilityRequest
            || !$declared->purpose()->equals($capability->purpose())
            || $declared->requirement() !== $capability->requirement()) {
            throw InvalidAgentRuntimeException::invalid('proposal_mismatch');
        }
        $permitted = false;
        foreach ($plan->capabilityDecisions() as $decision) {
            if ($decision->capabilityIdentity()->equals($capability->identity()) && $decision->permitted()) {
                $permitted = true;
                break;
            }
        }
        if (!$permitted) {
            throw InvalidAgentRuntimeException::invalid('proposal_capability_not_permitted');
        }

        return $capability->identity();
    }

    /** @param list<CapabilityIdentity> $identities */
    public static function portIndex(CapabilityIdentity $wanted, array $identities): int
    {
        foreach ($identities as $index => $identity) {
            if ($identity->equals($wanted)) {
                return $index;
            }
        }
        throw InvalidAgentRuntimeException::invalid('proposal_port_missing');
    }

    /**
     * @template T of object
     * @param array<array-key, mixed> $values
     * @param class-string<T> $type
     * @param callable(T): string $key
     * @return list<T>
     */
    private static function canonicalList(array $values, int $maximum, string $type, callable $key, string $name): array
    {
        if (!array_is_list($values)) {
            throw InvalidAgentRuntimeException::invalid('values_not_list');
        }
        if (count($values) > $maximum) {
            throw InvalidAgentRuntimeException::invalid('value_count');
        }
        $validated = [];
        $seen = [];
        $previous = null;
        foreach ($values as $value) {
            if (!$value instanceof $type) {
                throw InvalidAgentRuntimeException::invalid('invalid_value');
            }
            $current = $key($value);
            if (isset($seen[$current])) {
                throw InvalidAgentRuntimeException::invalid('duplicate_value');
            }
            if (null !== $previous && strcmp($previous, $current) >= 0) {
                throw InvalidAgentRuntimeException::invalid('noncanonical_value_order');
            }
            $seen[$current] = true;
            $previous = $current;
            $validated[] = $value;
        }
        unset($name);
        return $validated;
    }

    /** @param list<AgentConstraint> $constraints */
    private static function validateBudget(AgentRuntimeBudget $budget, array $constraints): void
    {
        if ($budget->concurrencyLimit() < 1) {
            throw InvalidAgentRuntimeException::invalid('budget_concurrency');
        }
        $ceilings = array_fill_keys(array_map(static fn(ConstraintKind $kind): string => $kind->value, ConstraintKind::cases()), 0);
        foreach ($constraints as $constraint) {
            $ceilings[$constraint->kind()->value] = $constraint->value();
        }
        $requested = [
            ConstraintKind::StepLimit->value => $budget->stepLimit(),
            ConstraintKind::DurationMillisecondsLimit->value => $budget->durationMillisecondsLimit(),
            ConstraintKind::TokenLimit->value => $budget->tokenLimit(),
            ConstraintKind::CapabilityCallLimit->value => $budget->capabilityCallLimit(),
            ConstraintKind::RetryLimit->value => $budget->retryLimit(),
            ConstraintKind::DelegationDepthLimit->value => $budget->delegationDepthLimit(),
            ConstraintKind::OutputBytesLimit->value => $budget->outputBytesLimit(),
            ConstraintKind::RetainedStateBytesLimit->value => $budget->retainedStateBytesLimit(),
            ConstraintKind::ConcurrencyLimit->value => $budget->concurrencyLimit(),
        ];
        foreach ($requested as $kind => $value) {
            if ($value > $ceilings[$kind]) {
                throw InvalidAgentRuntimeException::invalid('budget_exceeds_declaration');
            }
        }
    }

    /**
     * @param list<AgentConstraint> $left
     * @param list<AgentConstraint> $right
     */
    private static function sameConstraints(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }
        foreach ($left as $index => $value) {
            if ($value->kind() !== $right[$index]->kind() || $value->value() !== $right[$index]->value()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param list<EvidenceReference> $left
     * @param list<EvidenceReference> $right
     */
    private static function sameEvidence(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }
        foreach ($left as $index => $value) {
            $other = $right[$index];
            if ($value->kind() !== $other->kind() || !$value->identity()->equals($other->identity()) || $value->sha256() !== $other->sha256()) {
                return false;
            }
        }
        return true;
    }

    private static function sameCapabilityPlan(AgentDeclaration $declaration, AgentAuthorityPlan $plan): bool
    {
        $requests = $declaration->capabilityRequests();
        $decisions = $plan->capabilityDecisions();
        if (count($requests) !== count($decisions)) {
            return false;
        }
        foreach ($requests as $index => $request) {
            $decision = $decisions[$index];
            if (!$request->identity()->equals($decision->capabilityIdentity()) || $request->requirement() !== $decision->declaredRequirement()) {
                return false;
            }
        }
        return true;
    }

    private function __construct() {}
}
