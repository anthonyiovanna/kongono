<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Tests\Unit\Projection;

use Kongono\AssetInspection\Derivation\AssetIntelligenceDeriver;
use Kongono\AssetInspection\Inspection\AssetInspection;
use Kongono\AssetInspection\Inspection\AssetInspectionRequest;
use Kongono\AssetInspection\Inspection\AssetInspector;
use Kongono\AssetInspection\Projection\AssetObservationProjector;
use Kongono\AssetInspection\Projection\AssetSecurityAuditProjector;
use Kongono\AssetInspection\Role\AssetInspectionDeveloperRole;
use Kongono\AssetInspection\Tests\Support\AssetInspectionFactory;
use Kongono\AssetInspection\Verification\AssetIntelligenceVerdict;
use Kongono\AssetInspection\Verification\AssetIntelligenceVerifier;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\ObservabilityContext;
use PHPUnit\Framework\TestCase;

final class AssetProjectionVerificationTest extends TestCase
{
    public function testNeutralProjectionsAndIndependentVerificationPass(): void
    {
        $request = new AssetInspectionRequest(AssetInspectionFactory::current());
        $inspection = (new AssetInspector())->inspect($request);
        $graph = AssetInspectionFactory::graph(\Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole::Hantei);
        $view = (new AssetIntelligenceDeriver())->derive($inspection, AssetInspectionDeveloperRole::Hantei, $graph);
        $context = ObservabilityContext::uncorrelated();
        $observation = (new AssetObservationProjector())->project($inspection, $context);
        $audit = (new AssetSecurityAuditProjector())->project($inspection, $context);
        $verification = (new AssetIntelligenceVerifier())->verify($request, $inspection, $view, $observation, $audit, $graph);

        self::assertSame('kongono.asset.inspection', $observation->identity()->name());
        self::assertSame('constructed-not-recorded', $this->attribute($observation->attributes(), 'inspection.projection'));
        self::assertSame(DiagnosticDisclosure::Safe, $audit->disclosure());
        self::assertSame(AssetIntelligenceVerdict::Pass, $verification->verdict());
        self::assertSame([], $verification->failureTokens());
        self::assertNotContains(false, $verification->checks());
    }

    public function testVerifierRejectsCoherentButWrongInspectionWithoutRepair(): void
    {
        $request = new AssetInspectionRequest(AssetInspectionFactory::current());
        $expected = (new AssetInspector())->inspect($request);
        $bindings = $expected->protectedBindings();
        $bindings['build_sha256'] = str_repeat('f', 64);
        $tampered = new AssetInspection($expected->producerKind(), $bindings, $expected->counts(), $expected->flags());
        $view = (new AssetIntelligenceDeriver())->derive($tampered, AssetInspectionDeveloperRole::Chie);
        $context = ObservabilityContext::uncorrelated();
        $verification = (new AssetIntelligenceVerifier())->verify(
            $request,
            $tampered,
            $view,
            (new AssetObservationProjector())->project($tampered, $context),
            (new AssetSecurityAuditProjector())->project($tampered, $context),
        );

        self::assertSame(AssetIntelligenceVerdict::Fail, $verification->verdict());
        self::assertContains('inspection-identity-and-evidence-failed', $verification->failureTokens());
        self::assertSame($tampered->toArray(), $view->inspection()->toArray());
    }

    public function testVerifierReturnsDeterministicGraphAndProjectionFailureTokens(): void
    {
        $request = new AssetInspectionRequest(AssetInspectionFactory::current());
        $inspection = (new AssetInspector())->inspect($request);
        $graph = AssetInspectionFactory::graph();
        $view = (new AssetIntelligenceDeriver())->derive($inspection, AssetInspectionDeveloperRole::Chie, $graph);
        $context = ObservabilityContext::uncorrelated();
        $empty = (new AssetInspector())->inspect(new AssetInspectionRequest(AssetInspectionFactory::empty()));
        $verification = (new AssetIntelligenceVerifier())->verify(
            $request,
            $inspection,
            $view,
            (new AssetObservationProjector())->project($empty, $context),
            (new AssetSecurityAuditProjector())->project($empty, $context),
            AssetInspectionFactory::graph(services: 5),
        );

        self::assertSame(AssetIntelligenceVerdict::Fail, $verification->verdict());
        self::assertSame([
            'graph-evidence-failed',
            'observation-projection-failed',
            'security-audit-projection-failed',
        ], $verification->failureTokens());
        self::assertSame($verification->toArray(), (new AssetIntelligenceVerifier())->verify(
            $request,
            $inspection,
            $view,
            (new AssetObservationProjector())->project($empty, $context),
            (new AssetSecurityAuditProjector())->project($empty, $context),
            AssetInspectionFactory::graph(services: 5),
        )->toArray());
    }

    private function attribute(\Kongono\Observability\AttributeSet $attributes, string $key): bool|int|float|string|null
    {
        return $attributes->find(new \Kongono\Observability\AttributeKey($key))?->value();
    }
}
