<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Verification;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\AssetInspection\Derivation\AssetIntelligenceView;
use Kongono\AssetInspection\Graph\AssetGraphEvidenceProjector;
use Kongono\AssetInspection\Inspection\AssetInspection;
use Kongono\AssetInspection\Inspection\AssetInspectionRequest;
use Kongono\AssetInspection\Inspection\AssetInspector;
use Kongono\AssetInspection\Internal\CanonicalJson;
use Kongono\AssetInspection\Projection\AssetObservationProjector;
use Kongono\AssetInspection\Projection\AssetSecurityAuditProjector;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\Observation;
use Kongono\SecurityAudit\SecurityAuditEvidence;

/** Independently reconstructs and compares all supplied inspection evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetIntelligenceVerifier
{
    public function verify(
        AssetInspectionRequest $request,
        AssetInspection $claimedInspection,
        AssetIntelligenceView $candidateView,
        Observation $candidateObservation,
        SecurityAuditEvidence $candidateAudit,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): AssetIntelligenceVerification {
        $expectedInspection = (new AssetInspector())->inspect($request);
        $requestArtifact = hash_equals(
            $request->current()->resolutionArtifact()->identity()->value(),
            $expectedInspection->protectedBindings()['resolution_sha256'],
        );
        $inspection = $expectedInspection->toArray() === $claimedInspection->toArray()
            && $claimedInspection->toArray() === $candidateView->inspection()->toArray();
        $rolePurpose = $candidateView->purpose() === $candidateView->role()->purpose();
        $statements = AssetIntelligenceView::STATEMENTS === $candidateView->statements()
            && AssetIntelligenceView::LIMITATIONS === $candidateView->limitations();
        $expectedGraph = (new AssetGraphEvidenceProjector())->project($claimedInspection, $candidateView->role(), $graphView);
        $graph = $expectedGraph->toArray() === $candidateView->graphEvidence()->toArray()
            && 'not-established' === $candidateView->graphEvidence()->relationship();
        $serialized = $candidateView->toArray();
        $observedDigest = $serialized['serialization_sha256'] ?? null;
        unset($serialized['serialization_sha256']);
        $markers = is_string($observedDigest)
            && hash_equals(hash('sha256', CanonicalJson::encode($serialized)), $observedDigest)
            && $this->fixedMarkers($serialized);
        $expectedObservation = (new AssetObservationProjector())->project($claimedInspection, $candidateObservation->context());
        $observation = $this->observation($expectedObservation, $candidateObservation);
        $expectedAudit = (new AssetSecurityAuditProjector())->project($claimedInspection, $candidateAudit->context());
        $audit = $this->audit($expectedAudit, $candidateAudit);
        $checks = [
            'request-artifact-reconstruction' => $requestArtifact,
            'inspection-identity-and-evidence' => $inspection,
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

        return new AssetIntelligenceVerification(
            [] === $failures ? AssetIntelligenceVerdict::Pass : AssetIntelligenceVerdict::Fail,
            $candidateView->role(),
            $claimedInspection->identity(),
            $checks,
            $failures,
        );
    }

    /** @param array<string, mixed> $value */
    private function fixedMarkers(array $value): bool
    {
        foreach (['role_execution', 'repair', 'filesystem_access', 'source_output_reading', 'building', 'resolution', 'network_access', 'recording', 'exporting', 'production_observation', 'certification'] as $key) {
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
