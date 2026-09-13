<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ViewInspection\Derivation\ViewIntelligenceView;
use Kongono\ViewInspection\Graph\ViewGraphEvidence;
use Kongono\ViewInspection\Inspection\ViewInspection;
use Kongono\ViewInspection\Internal\CanonicalJson;

/** Independently compares evidence without calling the deriver or repairing it. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewIntelligenceVerifier
{
    public function verify(
        ViewInspection $inspection,
        ViewIntelligenceView $candidateView,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): ViewIntelligenceVerification {
        $expected = $inspection->toArray();
        $candidate = $candidateView->inspection()->toArray();
        $identitySchema = ViewInspection::SCHEMA === ($candidate['schema'] ?? null)
            && ViewInspection::VERSION === ($candidate['version'] ?? null)
            && ViewInspection::SCOPE === ($candidate['scope'] ?? null)
            && ViewInspection::DISCLOSURE === ($candidate['disclosure'] ?? null)
            && ViewInspection::AUTHORITY === ($candidate['authority'] ?? null)
            && $inspection->identity() === ($candidate['inspection_identity'] ?? null);
        $bindings = $this->sameKeys($expected, $candidate, ['protected_bindings']);
        $aggregates = $this->sameKeys($expected, $candidate, ['format_versions', 'counts', 'flags']);
        $statements = ViewIntelligenceView::STATEMENTS === $candidateView->statements();
        $limitations = ViewIntelligenceView::LIMITATIONS === $candidateView->limitations()
            && $this->sameKeys($expected, $candidate, ['limitations']);
        $graph = $this->graphArray($inspection, $candidateView, $graphView) === $candidateView->graphEvidence()->toArray();
        $rolePurpose = $candidateView->purpose() === $candidateView->role()->purpose();
        $markers = 'not-performed' === $candidateView->roleExecution()
            && 'not-performed' === $candidateView->repair()
            && 'none' === $candidateView->hiddenDataAccess()
            && 'not-performed' === $candidateView->filesystemAccess()
            && 'not-performed' === $candidateView->generatedPhpExecution()
            && 'not-performed' === $candidateView->rendering()
            && 'not-performed' === $candidateView->productionObservation()
            && 'not-performed' === $candidateView->recording()
            && 'not-performed' === $candidateView->exporting()
            && 'not-performed' === $candidateView->certification()
            && 'evidence-only' === $candidateView->authority();
        $serialized = $candidateView->toArray();
        $observedDigest = $serialized['serialization_sha256'] ?? null;
        unset($serialized['serialization_sha256']);
        $serialization = is_string($observedDigest) && hash_equals(hash('sha256', CanonicalJson::encode($serialized)), $observedDigest);
        $checks = [
            'role-purpose' => $rolePurpose,
            'inspection-identity-schema' => $identitySchema,
            'protected-binding-evidence' => $bindings,
            'format-aggregate-and-flag-evidence' => $aggregates,
            'ordered-statement-evidence' => $statements,
            'ordered-limitation-evidence' => $limitations,
            'graph-evidence' => $graph,
            'no-effect-markers' => $markers,
            'deterministic-serialization' => $serialization,
        ];
        $failures = [];
        foreach ($checks as $id => $passed) {
            if (!$passed) {
                $failures[] = $id . '-failed';
            }
        }

        return new ViewIntelligenceVerification(
            [] === $failures ? ViewIntelligenceVerdict::Pass : ViewIntelligenceVerdict::Fail,
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
    private function graphArray(ViewInspection $inspection, ViewIntelligenceView $candidateView, ?ApplicationGraphIntelligenceView $graphView): array
    {
        if (null === $graphView) {
            return (new ViewGraphEvidence($inspection->identity(), $candidateView->role(), false))->toArray();
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

        return (new ViewGraphEvidence(
            $inspection->identity(),
            $candidateView->role(),
            true,
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
