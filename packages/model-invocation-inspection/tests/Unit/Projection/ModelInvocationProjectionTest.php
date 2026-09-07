<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Tests\Unit\Projection;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspectionRequest;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspector;
use Kongono\ModelInvocationInspection\Projection\ModelInvocationObservationProjector;
use Kongono\ModelInvocationInspection\Projection\ModelInvocationSecurityAuditProjector;
use Kongono\ModelInvocationInspection\Tests\Support\ModelInvocationInspectionFactory;
use Kongono\Observability\ObservabilityContext;
use PHPUnit\Framework\TestCase;

final class ModelInvocationProjectionTest extends TestCase
{
    public function testNeutralProjectionsAreSafeValuesAndDoNotClaimDeliveryOrSecurity(): void
    {
        $profile = ModelInvocationInspectionFactory::profile();
        $inspection = (new ModelInvocationInspector())->inspect(new ModelInvocationInspectionRequest(
            $profile,
            ModelInvocationInspectionFactory::wiring($profile),
            ModelInvocationInspectionFactory::runtime($profile->source()),
        ));
        $context = ObservabilityContext::uncorrelated();
        $observation = (new ModelInvocationObservationProjector())->project($inspection, $context);
        $audit = (new ModelInvocationSecurityAuditProjector())->project($inspection, $context);

        self::assertSame('kongono.model-invocation.inspection', $observation->identity()->name());
        self::assertSame(1, $observation->identity()->schemaVersion());
        self::assertSame('kongono.model-invocation.inspection.security', $audit->definition()->identity()->name());
        self::assertSame(DiagnosticDisclosure::Safe, $audit->disclosure());

        $keys = array_map(static fn($attribute): string => $attribute->key()->value(), $observation->attributes()->attributes());
        foreach (['identity', 'sha256', 'provider', 'model', 'adapter', 'receipt', 'currency', 'metric', 'path', 'url', 'delivered', 'durable', 'compliant', 'secure'] as $forbidden) {
            self::assertSame([], array_filter($keys, static fn(string $key): bool => str_contains($key, $forbidden)));
        }
    }
}
