<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\NetworkEgressInspection\Derivation\NetworkEgressIntelligenceView;
use Kongono\NetworkEgressInspection\Graph\NetworkEgressGraphEvidence;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspection;
use Kongono\NetworkEgressInspection\Internal\CanonicalJson;

/** Independently reconstructs and compares permitted evidence without derivation or repair. */
#[Api(ApiStatus::Experimental)]
final readonly class NetworkEgressIntelligenceVerifier
{
    public function verify(
        NetworkEgressInspection $inspection,
        NetworkEgressIntelligenceView $candidate,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): NetworkEgressIntelligenceVerification {
        $expectedInspection = $inspection->toArray();
        $candidateInspection = $candidate->inspection()->toArray();
        $identitySchema = NetworkEgressInspection::SCHEMA === ($candidateInspection['schema'] ?? null)
            && NetworkEgressInspection::VERSION === ($candidateInspection['version'] ?? null)
            && $inspection->identity() === ($candidateInspection['inspection_identity'] ?? null);
        $bindings = $this->sameKeys($expectedInspection, $candidateInspection, ['protected_bindings', 'profile_version', 'wiring_version']);
        $aggregates = $this->sameKeys($expectedInspection, $candidateInspection, [
            'allowed_origin_count',
            'required_capability_count',
            'maximum_connection_attempts',
            'maximum_redirects',
            'maximum_resolution_candidates',
            'policy_posture',
        ]);
        $runtime = $this->sameKeys($expectedInspection, $candidateInspection, ['runtime_evidence_supplied', 'runtime_summary']);
        $semantics = $this->sameKeys($expectedInspection, $candidateInspection, ['limitations']);
        $graph = $this->graphArray($inspection, $candidate, $graphView) === $candidate->graphEvidence()->toArray();
        $rolePurpose = $candidate->purpose() === $candidate->role()->purpose();
        $roleSemantics = ['inspection-bindings', 'optional-runtime-evidence', 'graph-aggregates', 'limitations'] === $candidate->evidenceGroups()
            && NetworkEgressIntelligenceView::statementsFor($candidate->role()) === $candidate->statements()
            && 'not-performed' === $candidate->roleExecution()
            && 'not-performed' === $candidate->networkAccess()
            && 'not-performed' === $candidate->authorityEvaluation()
            && 'not-performed' === $candidate->repair()
            && 'not-performed' === $candidate->replay()
            && 'not-performed' === $candidate->certification()
            && 'none' === $candidate->hiddenDataAccess()
            && 'evidence-only' === $candidate->authority();
        $serialized = $candidate->toArray();
        $observedDigest = $serialized['serialization_sha256'] ?? null;
        unset($serialized['serialization_sha256']);
        $serialization = is_string($observedDigest)
            && hash_equals(hash('sha256', CanonicalJson::encode($serialized)), $observedDigest);

        $checks = [
            'role-purpose' => $rolePurpose,
            'inspection-identity-schema' => $identitySchema,
            'protected-binding-evidence' => $bindings,
            'policy-aggregate-evidence' => $aggregates,
            'runtime-summary-evidence' => $runtime,
            'graph-evidence' => $graph,
            'disclosure-currentness-authority-semantics' => $semantics,
            'role-statements-and-nonexecution-semantics' => $roleSemantics,
            'deterministic-serialization' => $serialization,
        ];
        $failures = [];
        foreach ($checks as $id => $passed) {
            if (!$passed) {
                $failures[] = $id . '-failed';
            }
        }

        return new NetworkEgressIntelligenceVerification(
            [] === $failures ? NetworkEgressIntelligenceVerdict::Pass : NetworkEgressIntelligenceVerdict::Fail,
            $candidate->role(),
            $inspection->identity(),
            $checks,
            $failures,
        );
    }

    /**
     * @param array<string, mixed> $expected
     * @param array<string, mixed> $candidate
     * @param list<string> $keys
     */
    private function sameKeys(array $expected, array $candidate, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $expected) || !array_key_exists($key, $candidate) || $expected[$key] !== $candidate[$key]) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, mixed> */
    private function graphArray(
        NetworkEgressInspection $inspection,
        NetworkEgressIntelligenceView $candidate,
        ?ApplicationGraphIntelligenceView $graphView,
    ): array {
        if (null === $graphView) {
            return (new NetworkEgressGraphEvidence($inspection->identity(), $candidate->role(), false))->toArray();
        }
        if ($graphView->role()->value !== $candidate->role()->value) {
            return [];
        }
        $raw = $graphView->toArray();
        $nodes = $raw['node_type_counts'] ?? null;
        $relations = $raw['relation_type_counts'] ?? null;
        if (!is_array($nodes) || !is_array($relations)) {
            return [];
        }

        return (new NetworkEgressGraphEvidence(
            $inspection->identity(),
            $candidate->role(),
            true,
            $graphView->artifactSha256(),
            $graphView->graphIdentity()->value(),
            $graphView->contractVersion()->value,
            $graphView->nodeCount(),
            $graphView->relationCount(),
            $nodes,
            $relations,
            $graphView->scope(),
            $graphView->visibility(),
            $graphView->currentness(),
            $graphView->authority(),
            $graphView->uncertainties(),
        ))->toArray();
    }
}
