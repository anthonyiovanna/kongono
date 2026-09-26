<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\Observation;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\StorageInspection\Derivation\StorageIntelligenceView;
use Kongono\StorageInspection\Graph\StorageGraphEvidenceProjector;
use Kongono\StorageInspection\Inspection\StorageInspection;
use Kongono\StorageInspection\Inspection\StorageInspectionRequest;
use Kongono\StorageInspection\Inspection\StorageInspector;
use Kongono\StorageInspection\Internal\CanonicalJson;
use Kongono\StorageInspection\Projection\StorageObservationProjector;
use Kongono\StorageInspection\Projection\StorageSecurityAuditProjector;

/** Independently reconstructs and compares all supplied storage inspection evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageIntelligenceVerifier
{
    public function verify(
        StorageInspectionRequest $request,
        StorageInspection $claimedInspection,
        StorageIntelligenceView $candidateView,
        Observation $candidateObservation,
        SecurityAuditEvidence $candidateAudit,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): StorageIntelligenceVerification {
        $expectedInspection = (new StorageInspector())->inspect($request);
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
        $statements = StorageIntelligenceView::STATEMENTS === $candidateView->statements()
            && StorageIntelligenceView::LIMITATIONS === $candidateView->limitations();
        $expectedGraph = (new StorageGraphEvidenceProjector())->project($expectedInspection, $candidateView->role(), $graphView);
        $graph = $expectedGraph->toArray() === $candidateView->graphEvidence()->toArray()
            && 'not-established' === $candidateView->graphEvidence()->relationship();
        $serialized = $candidateView->toArray();
        $observedDigest = $serialized['serialization_sha256'] ?? null;
        unset($serialized['serialization_sha256']);
        $markers = is_string($observedDigest)
            && hash_equals(hash('sha256', CanonicalJson::encode($serialized)), $observedDigest)
            && $this->fixedMarkers($serialized);
        $expectedObservation = (new StorageObservationProjector())->project($expectedInspection, $candidateObservation->context());
        $observation = $this->observation($expectedObservation, $candidateObservation);
        $expectedAudit = (new StorageSecurityAuditProjector())->project($expectedInspection, $candidateAudit->context());
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

        return new StorageIntelligenceVerification(
            [] === $failures ? StorageIntelligenceVerdict::Pass : StorageIntelligenceVerdict::Fail,
            $candidateView->role(),
            $claimedInspection->identity(),
            $checks,
            $failures,
        );
    }

    /** @param array<string, mixed> $value */
    private function fixedMarkers(array $value): bool
    {
        foreach (['role_execution', 'repair', 'apply', 'filesystem_access', 'provider_access', 'adapter_access', 'credential_validation', 'authorization_execution', 'runtime_access', 'clock_read', 'mutation', 'network_access', 'recording', 'exporting', 'production_observation', 'certification'] as $key) {
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
