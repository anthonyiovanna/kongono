<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ExternalServiceInspection\Derivation\ExternalServiceIntelligenceView;
use Kongono\ExternalServiceInspection\Graph\ExternalServiceGraphEvidence;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspection;
use Kongono\ExternalServiceInspection\Internal\CanonicalJson;

/** Independently reconstructs and compares permitted evidence without derivation or repair. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceIntelligenceVerifier
{
    public function verify(
        ExternalServiceInspection $inspection,
        ExternalServiceIntelligenceView $candidate,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): ExternalServiceIntelligenceVerification {
        $expectedInspection = $inspection->toArray();
        $candidateInspection = $candidate->inspection()->toArray();
        $identitySchema = ExternalServiceInspection::SCHEMA === ($candidateInspection['schema'] ?? null)
            && ExternalServiceInspection::VERSION === ($candidateInspection['version'] ?? null)
            && $inspection->identity() === ($candidateInspection['inspection_identity'] ?? null);
        $bindings = $this->sameKeys($expectedInspection, $candidateInspection, ['protected_bindings']);
        $aggregates = $this->sameKeys($expectedInspection, $candidateInspection, [
            'binding_count',
            'allowed_origin_count',
            'required_capability_count',
            'policy_budget',
            'policy_posture',
        ]);
        $runtime = $this->sameKeys($expectedInspection, $candidateInspection, ['completed_result_supplied', 'completed_result_summary']);
        $semantics = $this->sameKeys($expectedInspection, $candidateInspection, ['limitations']);
        $graph = $this->graphArray($inspection, $candidate, $graphView) === $candidate->graphEvidence()->toArray();
        $rolePurpose = $candidate->purpose() === $candidate->role()->purpose();
        $roleSemantics = ['inspection-bindings', 'optional-completed-result-evidence', 'graph-aggregates', 'limitations'] === $candidate->evidenceGroups()
            && ExternalServiceIntelligenceView::statementsFor($candidate->role()) === $candidate->statements()
            && 'not-performed' === $candidate->roleExecution()
            && 'not-performed' === $candidate->credentialAccess()
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
            'completed-result-summary-evidence' => $runtime,
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

        return new ExternalServiceIntelligenceVerification(
            [] === $failures ? ExternalServiceIntelligenceVerdict::Pass : ExternalServiceIntelligenceVerdict::Fail,
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
        ExternalServiceInspection $inspection,
        ExternalServiceIntelligenceView $candidate,
        ?ApplicationGraphIntelligenceView $graphView,
    ): array {
        if (null === $graphView) {
            return (new ExternalServiceGraphEvidence($inspection->identity(), $candidate->role(), false))->toArray();
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

        return (new ExternalServiceGraphEvidence(
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
