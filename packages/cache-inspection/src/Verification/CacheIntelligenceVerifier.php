<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\CacheInspection\Derivation\CacheIntelligenceView;
use Kongono\CacheInspection\Graph\CacheGraphEvidenceProjector;
use Kongono\CacheInspection\Inspection\CacheInspection;
use Kongono\CacheInspection\Inspection\CacheInspectionRequest;
use Kongono\CacheInspection\Inspection\CacheInspector;
use Kongono\CacheInspection\Internal\CanonicalJson;
use Kongono\CacheInspection\Projection\CacheObservationProjector;
use Kongono\CacheInspection\Projection\CacheSecurityAuditProjector;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\Observation;
use Kongono\SecurityAudit\SecurityAuditEvidence;

/** Independently reconstructs and compares all supplied cache inspection evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheIntelligenceVerifier
{
    public function verify(
        CacheInspectionRequest $request,
        CacheInspection $claimedInspection,
        CacheIntelligenceView $candidateView,
        Observation $candidateObservation,
        SecurityAuditEvidence $candidateAudit,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): CacheIntelligenceVerification {
        $expectedInspection = (new CacheInspector())->inspect($request);
        $current = $request->current();
        $bindings = $expectedInspection->protectedBindings();
        $requestEvidence = hash_equals($current->profiles()->identity()->value(), $bindings['profile_set_sha256'])
            && hash_equals($current->artifact()->buildIdentity()->value(), $bindings['build_sha256'])
            && hash_equals($current->artifact()->artifactIdentity()->value(), $bindings['artifact_sha256']);
        $inspection = $expectedInspection->toArray() === $claimedInspection->toArray()
            && $claimedInspection->toArray() === $candidateView->inspection()->toArray();
        $runtime = $expectedInspection->runtimeEvidenceStatus() === $claimedInspection->runtimeEvidenceStatus()
            && $expectedInspection->counts() === $claimedInspection->counts()
            && $expectedInspection->protectedBindings() === $claimedInspection->protectedBindings();
        $rolePurpose = $candidateView->purpose() === $candidateView->role()->purpose();
        $statements = CacheIntelligenceView::STATEMENTS === $candidateView->statements()
            && CacheIntelligenceView::LIMITATIONS === $candidateView->limitations();
        $expectedGraph = (new CacheGraphEvidenceProjector())->project($claimedInspection, $candidateView->role(), $graphView);
        $graph = $expectedGraph->toArray() === $candidateView->graphEvidence()->toArray()
            && 'not-established' === $candidateView->graphEvidence()->relationship();
        $serialized = $candidateView->toArray();
        $observedDigest = $serialized['serialization_sha256'] ?? null;
        unset($serialized['serialization_sha256']);
        $markers = is_string($observedDigest)
            && hash_equals(hash('sha256', CanonicalJson::encode($serialized)), $observedDigest)
            && $this->fixedMarkers($serialized);
        $expectedObservation = (new CacheObservationProjector())->project($claimedInspection, $candidateObservation->context());
        $observation = $this->observation($expectedObservation, $candidateObservation);
        $expectedAudit = (new CacheSecurityAuditProjector())->project($claimedInspection, $candidateAudit->context());
        $audit = $this->audit($expectedAudit, $candidateAudit);
        $checks = [
            'request-profile-artifact-reconstruction' => $requestEvidence,
            'inspection-identity-and-evidence' => $inspection,
            'runtime-status-counts-and-bindings' => $runtime,
            'role-purpose' => $rolePurpose,
            'ordered-statements-and-limitations' => $statements,
            'graph-evidence' => $graph,
            'no-effect-markers-and-serialization' => $markers,
            'observation-projection' => $observation,
            'security-audit-projection' => $audit,
        ];
        $failures = [];
        foreach ($checks as $id => $passed) {
            if (!$passed) {
                $failures[] = $id . '-failed';
            }
        }

        return new CacheIntelligenceVerification(
            [] === $failures ? CacheIntelligenceVerdict::Pass : CacheIntelligenceVerdict::Fail,
            $candidateView->role(),
            $claimedInspection->identity(),
            $checks,
            $failures,
        );
    }

    /** @param array<string, mixed> $value */
    private function fixedMarkers(array $value): bool
    {
        foreach (['role_execution', 'repair', 'apply', 'filesystem_access', 'backend_access', 'runtime_access', 'clock_read', 'mutation', 'network_access', 'recording', 'exporting', 'production_observation', 'certification'] as $key) {
            if ('not-performed' !== ($value[$key] ?? null)) {
                return false;
            }
        }

        return 'none' === ($value['hidden_data_access'] ?? null) && 'evidence-only' === ($value['authority'] ?? null);
    }

    private function observation(Observation $expected, Observation $candidate): bool
    {
        return $expected->identity()->equals($candidate->identity())
            && $expected->kind() === $candidate->kind()
            && $expected->status() === $candidate->status()
            && $this->attributes($expected->attributes()) === $this->attributes($candidate->attributes());
    }

    private function audit(SecurityAuditEvidence $expected, SecurityAuditEvidence $candidate): bool
    {
        return $expected->definition()->identity()->equals($candidate->definition()->identity())
            && $expected->definition()->retentionIntent() === $candidate->definition()->retentionIntent()
            && $expected->outcome() === $candidate->outcome()
            && $expected->disclosure() === $candidate->disclosure()
            && $this->attributes($expected->attributes()) === $this->attributes($candidate->attributes());
    }

    /** @return array<string, array{kind: string, value: bool|int|float|string}> */
    private function attributes(AttributeSet $set): array
    {
        $result = [];
        foreach ($set->attributes() as $attribute) {
            $result[$attribute->key()->value()] = [
                'kind' => $attribute->value()->kind()->value,
                'value' => $attribute->value()->value(),
            ];
        }

        return $result;
    }
}
