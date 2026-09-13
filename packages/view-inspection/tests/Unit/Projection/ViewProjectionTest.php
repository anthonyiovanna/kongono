<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Tests\Unit\Projection;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\ObservabilityContext;
use Kongono\ViewInspection\Inspection\ViewInspectionRequest;
use Kongono\ViewInspection\Inspection\ViewInspector;
use Kongono\ViewInspection\Projection\ViewObservationProjector;
use Kongono\ViewInspection\Projection\ViewSecurityAuditProjector;
use Kongono\ViewInspection\Tests\Support\ViewInspectionFactory;
use PHPUnit\Framework\TestCase;

final class ViewProjectionTest extends TestCase
{
    public function testNeutralProjectionsAreConstructedSafeValuesWithoutDeliveryOrCertification(): void
    {
        $inspection = (new ViewInspector())->inspect(new ViewInspectionRequest(ViewInspectionFactory::profile()));
        $context = ObservabilityContext::uncorrelated();
        $observation = (new ViewObservationProjector())->project($inspection, $context);
        $audit = (new ViewSecurityAuditProjector())->project($inspection, $context);

        self::assertSame('kongono.view.inspection', $observation->identity()->name());
        self::assertSame(1, $observation->identity()->schemaVersion());
        self::assertSame('kongono.view.inspection.security', $audit->definition()->identity()->name());
        self::assertSame(DiagnosticDisclosure::Safe, $audit->disclosure());
        self::assertSame($context, $observation->context());
        self::assertSame($context, $audit->context());

        foreach ([$observation->attributes(), $audit->attributes()] as $attributes) {
            $projected = [];
            foreach ($attributes->attributes() as $attribute) {
                $projected[$attribute->key()->value()] = $attribute->value()->value();
            }
            self::assertSame('constructed-not-recorded', $projected['inspection.projection']);
            foreach (['identity', 'sha256', 'path', 'source', 'template', 'generated', 'rendered', 'header', 'recorded', 'exported', 'retained', 'certified', 'compliant', 'secure'] as $forbidden) {
                self::assertSame([], array_filter(array_keys($projected), static fn(string $key): bool => str_contains($key, $forbidden) && 'inspection.generated_php_executed' !== $key));
            }
        }
    }
}
