<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Tests\Security;

use Kongono\ModelInvocationInspection\Derivation\ModelInvocationIntelligenceDeriver;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspectionRequest;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspector;
use Kongono\ModelInvocationInspection\Projection\ModelInvocationObservationProjector;
use Kongono\ModelInvocationInspection\Projection\ModelInvocationSecurityAuditProjector;
use Kongono\ModelInvocationInspection\Role\ModelInspectionDeveloperRole;
use Kongono\ModelInvocationInspection\Tests\Support\ModelInvocationInspectionFactory;
use Kongono\Observability\ObservabilityContext;
use PHPUnit\Framework\TestCase;

final class ModelInvocationInspectionDisclosureTest extends TestCase
{
    public function testEverySerializedAndProjectedSurfaceOmitsRawAndArbitraryLabels(): void
    {
        $profile = ModelInvocationInspectionFactory::profile();
        $inspection = (new ModelInvocationInspector())->inspect(new ModelInvocationInspectionRequest(
            $profile,
            ModelInvocationInspectionFactory::wiring($profile),
            ModelInvocationInspectionFactory::runtime($profile->source()),
        ));
        $view = (new ModelInvocationIntelligenceDeriver())->derive($inspection, ModelInspectionDeveloperRole::Oshie);
        $context = ObservabilityContext::uncorrelated();
        $observation = (new ModelInvocationObservationProjector())->project($inspection, $context);
        $audit = (new ModelInvocationSecurityAuditProjector())->project($inspection, $context);
        $surface = $inspection->canonicalBytes() . $view->canonicalBytes();
        foreach ([$observation->attributes(), $audit->attributes()] as $attributes) {
            foreach ($attributes->attributes() as $attribute) {
                $surface .= $attribute->key()->value() . '=' . (string) $attribute->value()->value();
            }
        }
        foreach ([ModelInvocationInspectionFactory::SECRET, ModelInvocationInspectionFactory::RESULT_SECRET, 'provider.inspection', 'model.inspection', 'adapter.inspection', 'profile.inspection', 'plan.inspection', 'invocation.inspection', 'receipt.inspection', 'result.inspection', 'tokens', 'USD', 'credential', 'provider.example', '/api?'] as $secret) {
            self::assertStringNotContainsString($secret, $surface);
        }
    }
}
