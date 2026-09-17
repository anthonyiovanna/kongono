<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Tests\Unit\Projection;

use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphRole;
use Kongono\CacheInspection\Derivation\CacheIntelligenceDeriver;
use Kongono\CacheInspection\Inspection\CacheInspection;
use Kongono\CacheInspection\Inspection\CacheInspectionRequest;
use Kongono\CacheInspection\Inspection\CacheInspector;
use Kongono\CacheInspection\Projection\CacheObservationProjector;
use Kongono\CacheInspection\Projection\CacheSecurityAuditProjector;
use Kongono\CacheInspection\Role\CacheInspectionDeveloperRole;
use Kongono\CacheInspection\Tests\Support\CacheInspectionFactory;
use Kongono\CacheInspection\Verification\CacheIntelligenceVerdict;
use Kongono\CacheInspection\Verification\CacheIntelligenceVerifier;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\ObservabilityContext;
use PHPUnit\Framework\TestCase;

final class CacheProjectionVerificationTest extends TestCase
{
    public function testNeutralProjectionsAndIndependentVerificationPass(): void
    {
        $current = CacheInspectionFactory::current();
        $request = new CacheInspectionRequest($current, CacheInspectionFactory::runtime($current));
        $inspection = (new CacheInspector())->inspect($request);
        $graph = CacheInspectionFactory::graph(GraphRole::Hantei);
        $view = (new CacheIntelligenceDeriver())->derive($inspection, CacheInspectionDeveloperRole::Hantei, $graph);
        $context = ObservabilityContext::uncorrelated();
        $observation = (new CacheObservationProjector())->project($inspection, $context);
        $audit = (new CacheSecurityAuditProjector())->project($inspection, $context);
        $verification = (new CacheIntelligenceVerifier())->verify($request, $inspection, $view, $observation, $audit, $graph);

        self::assertSame('kongono.cache.inspection', $observation->identity()->name());
        $expectedAttributeKeys = [
            'inspection.schema',
            'inspection.schema_version',
            'inspection.runtime_evidence_status',
            'inspection.profile_count',
            ...array_map(static fn(string $outcome): string => 'inspection.' . $outcome, \Kongono\CacheInspection\RuntimeEvidence\DetachedCacheRuntimeEvidence::OUTCOMES),
            'inspection.outcome_total',
            'inspection.structurally_validated',
            'inspection.supplied_exact_current_evidence',
            'inspection.detached_runtime_evidence_supplied',
            'inspection.keys_omitted',
            'inspection.values_omitted',
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
        self::assertSame(CacheIntelligenceVerdict::Pass, $verification->verdict());
        self::assertSame([], $verification->failureTokens());
        self::assertNotContains(false, $verification->checks());
    }

    public function testVerifierRejectsBindingCountIdentityGraphStatementAndProjectionTampering(): void
    {
        $current = CacheInspectionFactory::current();
        $request = new CacheInspectionRequest($current, CacheInspectionFactory::runtime($current));
        $expected = (new CacheInspector())->inspect($request);
        $bindings = $expected->protectedBindings();
        $bindings['build_sha256'] = str_repeat('f', 64);
        $tampered = new CacheInspection($expected->runtimeEvidenceStatus(), $bindings, $expected->counts(), $expected->flags());
        $view = (new CacheIntelligenceDeriver())->derive($tampered, CacheInspectionDeveloperRole::Chie, CacheInspectionFactory::graph());
        $context = ObservabilityContext::uncorrelated();
        $empty = (new CacheInspector())->inspect(new CacheInspectionRequest(CacheInspectionFactory::current(true)));
        $verification = (new CacheIntelligenceVerifier())->verify(
            $request,
            $tampered,
            $view,
            (new CacheObservationProjector())->project($empty, $context),
            (new CacheSecurityAuditProjector())->project($empty, $context),
            CacheInspectionFactory::graph(services: 5),
        );

        self::assertSame(CacheIntelligenceVerdict::Fail, $verification->verdict());
        self::assertSame([
            'inspection-identity-and-evidence-failed',
            'runtime-status-counts-and-bindings-failed',
            'graph-evidence-failed',
            'observation-projection-failed',
            'security-audit-projection-failed',
        ], $verification->failureTokens());
        self::assertSame($verification->toArray(), (new CacheIntelligenceVerifier())->verify(
            $request,
            $tampered,
            $view,
            (new CacheObservationProjector())->project($empty, $context),
            (new CacheSecurityAuditProjector())->project($empty, $context),
            CacheInspectionFactory::graph(services: 5),
        )->toArray());
    }

    private function attribute(\Kongono\Observability\AttributeSet $attributes, string $key): bool|int|float|string|null
    {
        return $attributes->find(new \Kongono\Observability\AttributeKey($key))?->value();
    }
}
