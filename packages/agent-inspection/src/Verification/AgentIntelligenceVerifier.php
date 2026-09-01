<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Verification;

use Kongono\AgentInspection\Derivation\AgentIntelligenceView;
use Kongono\AgentInspection\Graph\AgentGraphEvidence;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspection;
use Kongono\AgentInspection\Internal\CanonicalJson;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;

/** Independently compares supplied role evidence without derivation or repair. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentIntelligenceVerifier
{
    public function verify(
        AgentPrincipalInspection $inspection,
        AgentIntelligenceView $view,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): AgentIntelligenceVerification {
        $expected = $inspection->toArray();
        $candidate = $view->inspection()->toArray();
        $identitySchema = AgentPrincipalInspection::SCHEMA === ($candidate['schema'] ?? null)
            && AgentPrincipalInspection::VERSION === ($candidate['version'] ?? null)
            && $inspection->identity() === ($candidate['inspection_identity'] ?? null);

        $aggregateKeys = [
            'profile_identity', 'declaration_artifact_sha256', 'declaration_identity', 'admission_identity',
            'generated_wiring_sha256', 'contract_version', 'declared_capability_count', 'required_capability_count',
            'optional_capability_count', 'permitted_capability_count', 'denied_capability_count', 'constraint_count',
            'evidence_reference_count', 'declared_visibility',
        ];
        $aggregates = $this->sameKeys($expected, $candidate, $aggregateKeys);
        $runtime = $this->sameKeys($expected, $candidate, ['runtime_evidence_supplied', 'runtime_summary']);
        $semantics = $this->sameKeys($expected, $candidate, [
            'scope', 'disclosure', 'profile_currentness', 'external_authority_currentness', 'authority',
            'application_state', 'provider_state', 'graph_relationship', 'trusted_code_isolation',
        ]);

        $expectedGraph = $this->graphArray($inspection, $view, $graphView);
        $graph = $expectedGraph === $view->graphEvidence()->toArray();
        $rolePurpose = $view->purpose() === $view->role()->purpose();
        $roleSemantics = 'not-performed' === $view->roleExecution() && 'not-performed' === $view->repair()
            && 'evidence-only' === $view->authority() && 'none' === $view->hiddenDataAccess();

        $serialized = $view->toArray();
        $observedDigest = $serialized['serialization_sha256'] ?? null;
        unset($serialized['serialization_sha256']);
        $serialization = is_string($observedDigest) && hash_equals(hash('sha256', CanonicalJson::encode($serialized)), $observedDigest);

        $checks = [
            'role-purpose' => $rolePurpose,
            'inspection-identity-schema' => $identitySchema,
            'inspection-aggregate-evidence' => $aggregates,
            'runtime-summary-evidence' => $runtime,
            'graph-evidence' => $graph,
            'disclosure-currentness-authority-semantics' => $semantics,
            'role-execution-repair-hidden-access-semantics' => $roleSemantics,
            'deterministic-serialization' => $serialization,
        ];
        $failures = [];
        foreach ($checks as $id => $passed) {
            if (!$passed) {
                $failures[] = $id . '-failed';
            }
        }

        return new AgentIntelligenceVerification(
            [] === $failures ? AgentIntelligenceVerdict::Pass : AgentIntelligenceVerdict::Fail,
            $view->role(),
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
    private function graphArray(AgentPrincipalInspection $inspection, AgentIntelligenceView $view, ?ApplicationGraphIntelligenceView $graphView): array
    {
        if (null === $graphView) {
            return (new AgentGraphEvidence($inspection->identity(), $view->role(), false))->toArray();
        }
        if ($graphView->role()->value !== $view->role()->value) {
            return [];
        }
        $raw = $graphView->toArray();
        $nodeTypeCounts = $raw['node_type_counts'] ?? null;
        $relationTypeCounts = $raw['relation_type_counts'] ?? null;
        if (!is_array($nodeTypeCounts) || !is_array($relationTypeCounts)) {
            return [];
        }

        return (new AgentGraphEvidence(
            $inspection->identity(),
            $view->role(),
            true,
            $graphView->artifactSha256(),
            $graphView->graphIdentity()->value(),
            $graphView->contractVersion()->value,
            $graphView->nodeCount(),
            $graphView->relationCount(),
            $nodeTypeCounts,
            $relationTypeCounts,
            $graphView->scope(),
            $graphView->visibility(),
            $graphView->currentness(),
            $graphView->authority(),
            $graphView->uncertainties(),
        ))->toArray();
    }
}
