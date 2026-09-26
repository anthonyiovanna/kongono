<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Tests\Unit\Projection;

use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\ObservabilityContext;
use Kongono\StorageInspection\Derivation\StorageIntelligenceDeriver;
use Kongono\StorageInspection\Inspection\StorageInspection;
use Kongono\StorageInspection\Inspection\StorageInspectionRequest;
use Kongono\StorageInspection\Inspection\StorageInspector;
use Kongono\StorageInspection\Projection\StorageObservationProjector;
use Kongono\StorageInspection\Projection\StorageSecurityAuditProjector;
use Kongono\StorageInspection\Role\StorageInspectionDeveloperRole;
use Kongono\StorageInspection\Tests\Support\StorageInspectionFactory;
use Kongono\StorageInspection\Verification\StorageIntelligenceVerdict;
use Kongono\StorageInspection\Verification\StorageIntelligenceVerifier;
use PHPUnit\Framework\TestCase;

final class StorageProjectionVerificationTest extends TestCase
{
    public function testNeutralProjectionsAndIndependentVerificationPass(): void
    {
        $current = StorageInspectionFactory::current();
        $request = new StorageInspectionRequest($current, StorageInspectionFactory::runtime($current));
        $inspection = (new StorageInspector())->inspect($request);
        $graph = StorageInspectionFactory::graph(GraphRole::Hantei);
        $view = (new StorageIntelligenceDeriver())->derive($inspection, StorageInspectionDeveloperRole::Hantei, $graph);
        $context = ObservabilityContext::uncorrelated();
        $observation = (new StorageObservationProjector())->project($inspection, $context);
        $audit = (new StorageSecurityAuditProjector())->project($inspection, $context);
        $verification = (new StorageIntelligenceVerifier())->verify($request, $inspection, $view, $observation, $audit, $graph);

        self::assertSame('kongono.storage.inspection', $observation->identity()->name());
        $expectedAttributeKeys = [
            'inspection.schema',
            'inspection.schema_version',
            'inspection.runtime_evidence_status',
            'inspection.profile_count',
            'inspection.outcome_total',
            'inspection.mutation_uncertain',
            'inspection.structurally_validated',
            'inspection.supplied_exact_current_evidence',
            'inspection.detached_runtime_evidence_supplied',
            'inspection.object_identities_omitted',
            'inspection.object_contents_omitted',
            'inspection.provider_access',
            'inspection.adapter_access',
            'inspection.runtime_observed',
            'inspection.production_observed',
            'inspection.scope',
            'inspection.posture',
            'inspection.disclosure',
            'inspection.authority',
            'inspection.projection',
        ];
        sort($expectedAttributeKeys, SORT_STRING);
        self::assertSame($expectedAttributeKeys, array_map(static fn(\Kongono\Observability\SignalAttribute $attribute): string => $attribute->key()->value(), $observation->attributes()->attributes()));
        self::assertSame($expectedAttributeKeys, array_map(static fn(\Kongono\Observability\SignalAttribute $attribute): string => $attribute->key()->value(), $audit->attributes()->attributes()));
        self::assertSame('constructed-not-recorded', $this->attribute($observation->attributes(), 'inspection.projection'));
        self::assertSame(DiagnosticDisclosure::Safe, $audit->disclosure());
        self::assertSame(StorageIntelligenceVerdict::Pass, $verification->verdict());
        self::assertSame([], $verification->failureTokens());
        self::assertNotContains(false, $verification->checks());
    }

    public function testVerifierRejectsBindingCountIdentityGraphStatementAndProjectionTampering(): void
    {
        $current = StorageInspectionFactory::current();
        $request = new StorageInspectionRequest($current, StorageInspectionFactory::runtime($current));
        $expected = (new StorageInspector())->inspect($request);
        $bindings = $expected->protectedBindings();
        $bindings['build_sha256'] = str_repeat('f', 64);
        $tampered = new StorageInspection($expected->runtimeEvidenceStatus(), $bindings, $expected->counts(), $expected->flags());
        $view = (new StorageIntelligenceDeriver())->derive($tampered, StorageInspectionDeveloperRole::Chie, StorageInspectionFactory::graph());
        $context = ObservabilityContext::uncorrelated();
        $single = (new StorageInspector())->inspect(new StorageInspectionRequest(StorageInspectionFactory::current(true)));
        $verification = (new StorageIntelligenceVerifier())->verify(
            $request,
            $tampered,
            $view,
            (new StorageObservationProjector())->project($single, $context),
            (new StorageSecurityAuditProjector())->project($single, $context),
            StorageInspectionFactory::graph(services: 5),
        );

        self::assertSame(StorageIntelligenceVerdict::Fail, $verification->verdict());
        self::assertSame([
            'inspection-identity-and-evidence-failed',
            'runtime-status-counts-and-bindings-failed',
            'graph-evidence-failed',
            'observation-projection-failed',
            'security-audit-projection-failed',
        ], $verification->failureTokens());
        self::assertSame($verification->toArray(), (new StorageIntelligenceVerifier())->verify(
            $request,
            $tampered,
            $view,
            (new StorageObservationProjector())->project($single, $context),
            (new StorageSecurityAuditProjector())->project($single, $context),
            StorageInspectionFactory::graph(services: 5),
        )->toArray());
    }

    public function testEachIndependentlyChangedVerifiableFieldFamilyFails(): void
    {
        $current = StorageInspectionFactory::current();
        $request = new StorageInspectionRequest($current, StorageInspectionFactory::runtime($current));
        $expected = (new StorageInspector())->inspect($request);
        $role = StorageInspectionDeveloperRole::Chie;
        $deriver = new StorageIntelligenceDeriver();
        $view = $deriver->derive($expected, $role);
        $context = ObservabilityContext::uncorrelated();
        $observation = (new StorageObservationProjector())->project($expected, $context);
        $audit = (new StorageSecurityAuditProjector())->project($expected, $context);
        $verifier = new StorageIntelligenceVerifier();
        $verify = static fn(StorageInspection $inspection, \Kongono\StorageInspection\Derivation\StorageIntelligenceView $candidateView, \Kongono\Observability\Observation $candidateObservation, \Kongono\SecurityAudit\SecurityAuditEvidence $candidateAudit, ?\Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView $graph = null) => $verifier->verify($request, $inspection, $candidateView, $candidateObservation, $candidateAudit, $graph);

        $bindings = $expected->protectedBindings();
        $bindings['build_sha256'] = str_repeat('f', 64);
        $changedBinding = new StorageInspection($expected->runtimeEvidenceStatus(), $bindings, $expected->counts(), $expected->flags());
        $bindingResult = $verify($changedBinding, $deriver->derive($changedBinding, $role), $observation, $audit);
        self::assertSame(StorageIntelligenceVerdict::Fail, $bindingResult->verdict());
        self::assertContains('inspection-identity-and-evidence-failed', $bindingResult->failureTokens());
        self::assertContains('runtime-status-counts-and-bindings-failed', $bindingResult->failureTokens());

        $counts = $expected->counts();
        $counts['read_completed']++;
        $counts['outcome_total']++;
        $changedCount = new StorageInspection($expected->runtimeEvidenceStatus(), $expected->protectedBindings(), $counts, $expected->flags());
        $countResult = $verify($changedCount, $deriver->derive($changedCount, $role), $observation, $audit);
        self::assertSame(StorageIntelligenceVerdict::Fail, $countResult->verdict());
        self::assertContains('runtime-status-counts-and-bindings-failed', $countResult->failureTokens());

        $absent = (new StorageInspector())->inspect(new StorageInspectionRequest($current));
        $statusResult = $verify($absent, $deriver->derive($absent, $role), $observation, $audit);
        self::assertSame(StorageIntelligenceVerdict::Fail, $statusResult->verdict());
        self::assertContains('runtime-status-counts-and-bindings-failed', $statusResult->failureTokens());

        $single = (new StorageInspector())->inspect(new StorageInspectionRequest(StorageInspectionFactory::current(true)));
        $otherObservation = (new StorageObservationProjector())->project($single, $context);
        $observationResult = $verify($expected, $view, $otherObservation, $audit);
        self::assertSame(['observation-projection-failed'], $observationResult->failureTokens());
        $otherAudit = (new StorageSecurityAuditProjector())->project($single, $context);
        $auditResult = $verify($expected, $view, $observation, $otherAudit);
        self::assertSame(['security-audit-projection-failed'], $auditResult->failureTokens());

        $graph = StorageInspectionFactory::graph();
        $differentGraph = StorageInspectionFactory::graph(services: 5);
        $graphView = $deriver->derive($expected, $role, $differentGraph);
        $graphResult = $verify($expected, $graphView, $observation, $audit, $graph);
        self::assertSame(['graph-evidence-failed'], $graphResult->failureTokens());
        self::assertSame('not-established', $graphView->graphEvidence()->relationship());
        self::assertSame('constructed-not-recorded', $this->attribute($observation->attributes(), 'inspection.projection'));
        self::assertSame('constructed-not-recorded', $this->attribute($audit->attributes(), 'inspection.projection'));
    }

    private function attribute(\Kongono\Observability\AttributeSet $attributes, string $key): bool|int|float|string|null
    {
        return $attributes->find(new \Kongono\Observability\AttributeKey($key))?->value();
    }
}
