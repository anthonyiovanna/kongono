<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Tests\Unit\Projection;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspectionRequest;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspector;
use Kongono\ExternalServiceInspection\Projection\ExternalServiceObservationProjector;
use Kongono\ExternalServiceInspection\Projection\ExternalServiceSecurityAuditProjector;
use Kongono\ExternalServiceInspection\Tests\Support\ExternalServiceInspectionFactory as Fixture;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationStatus;
use Kongono\SecurityAudit\SecurityAuditOutcome;
use PHPUnit\Framework\TestCase;

final class ExternalServiceProjectionTest extends TestCase
{
    public function testObservationAndAuditAreConstructedButNotRecorded(): void
    {
        $profile = Fixture::profile();
        $inspection = (new ExternalServiceInspector())->inspect(new ExternalServiceInspectionRequest($profile));
        $context = ObservabilityContext::uncorrelated();

        $observation = (new ExternalServiceObservationProjector())->project($inspection, $context);
        self::assertSame('kongono.external-service.inspection', $observation->identity()->name());
        self::assertSame(1, $observation->identity()->schemaVersion());
        self::assertSame(ObservationKind::OperationalEvent, $observation->kind());
        self::assertSame(ObservationStatus::Ok, $observation->status());
        self::assertSame(14, $observation->attributes()->count());

        $audit = (new ExternalServiceSecurityAuditProjector())->project($inspection, $context);
        self::assertSame('kongono.external-service.inspection.security', $audit->definition()->identity()->name());
        self::assertSame(SecurityAuditOutcome::Succeeded, $audit->outcome());
        self::assertSame(DiagnosticDisclosure::Safe, $audit->disclosure());
        self::assertSame(11, $audit->attributes()->count());
    }
}
