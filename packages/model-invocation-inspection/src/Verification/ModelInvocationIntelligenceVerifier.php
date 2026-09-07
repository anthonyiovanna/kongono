<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ModelInvocationInspection\Derivation\ModelInvocationIntelligenceView;
use Kongono\ModelInvocationInspection\Graph\ModelInvocationGraphEvidence;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspection;
use Kongono\ModelInvocationInspection\Internal\CanonicalJson;

/** Independently compares supplied evidence without calling the deriver or repairing it. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelInvocationIntelligenceVerifier
{
    public function verify(
        ModelInvocationInspection $inspection,
        ModelInvocationIntelligenceView $candidateView,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): ModelInvocationIntelligenceVerification {
        $expected = $inspection->toArray();
        $candidate = $candidateView->inspection()->toArray();
        $identitySchema = ModelInvocationInspection::SCHEMA === ($candidate['schema'] ?? null)
            && ModelInvocationInspection::VERSION === ($candidate['version'] ?? null)
            && $inspection->identity() === ($candidate['inspection_identity'] ?? null);
        $bindings = $this->sameKeys($expected, $candidate, ['protected_bindings', 'contract_version', 'profile_version', 'wiring_version']);
        $aggregates = $this->sameKeys($expected, $candidate, ['message_count', 'capability_count', 'usage_limit_count', 'streaming']);
        $runtime = $this->sameKeys($expected, $candidate, ['runtime_evidence_supplied', 'runtime_summary']);
        $semantics = $this->sameKeys($expected, $candidate, ['limitations']);
        $graph = $this->graphArray($inspection, $candidateView, $graphView) === $candidateView->graphEvidence()->toArray();
        $rolePurpose = $candidateView->purpose() === $candidateView->role()->purpose();
        $roleSemantics = 'not-performed' === $candidateView->roleExecution()
            && 'not-performed' === $candidateView->repair()
            && 'none' === $candidateView->hiddenDataAccess()
            && 'not-performed' === $candidateView->providerInvocation()
            && 'evidence-only' === $candidateView->authority();
        $serialized = $candidateView->toArray();
        $observedDigest = $serialized['serialization_sha256'] ?? null;
        unset($serialized['serialization_sha256']);
        $serialization = is_string($observedDigest) && hash_equals(hash('sha256', CanonicalJson::encode($serialized)), $observedDigest);
        $checks = [
            'role-purpose' => $rolePurpose,
            'inspection-identity-schema' => $identitySchema,
            'protected-binding-evidence' => $bindings,
            'request-aggregate-evidence' => $aggregates,
            'runtime-summary-evidence' => $runtime,
            'graph-evidence' => $graph,
            'disclosure-currentness-authority-semantics' => $semantics,
            'role-execution-repair-hidden-access-provider-semantics' => $roleSemantics,
            'deterministic-serialization' => $serialization,
        ];
        $failures = [];
        foreach ($checks as $id => $passed) {
            if (!$passed) {
                $failures[] = $id . '-failed';
            }
        }

        return new ModelInvocationIntelligenceVerification(
            [] === $failures ? ModelInvocationIntelligenceVerdict::Pass : ModelInvocationIntelligenceVerdict::Fail,
            $candidateView->role(),
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
    private function graphArray(ModelInvocationInspection $inspection, ModelInvocationIntelligenceView $candidateView, ?ApplicationGraphIntelligenceView $graphView): array
    {
        if (null === $graphView) {
            return (new ModelInvocationGraphEvidence($inspection->identity(), $candidateView->role(), false))->toArray();
        }
        if ($graphView->role()->value !== $candidateView->role()->value) {
            return [];
        }
        $raw = $graphView->toArray();
        $nodes = $raw['node_type_counts'] ?? null;
        $relations = $raw['relation_type_counts'] ?? null;
        if (!is_array($nodes) || !is_array($relations)) {
            return [];
        }

        return (new ModelInvocationGraphEvidence(
            $inspection->identity(),
            $candidateView->role(),
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
