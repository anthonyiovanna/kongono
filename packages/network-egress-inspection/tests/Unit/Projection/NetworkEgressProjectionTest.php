<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Tests\Unit\Projection;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspectionRequest;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspector;
use Kongono\NetworkEgressInspection\Projection\NetworkEgressObservationProjector;
use Kongono\NetworkEgressInspection\Projection\NetworkEgressSecurityAuditProjector;
use Kongono\NetworkEgressInspection\Tests\Support\NetworkEgressInspectionFactory as Fixture;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationStatus;
use Kongono\SecurityAudit\SecurityAuditOutcome;
use PHPUnit\Framework\TestCase;

final class NetworkEgressProjectionTest extends TestCase
{
    public function testObservationAndAuditAreConstructedButNotRecorded(): void
    {
        $profile = Fixture::profile();
        $inspection = (new NetworkEgressInspector())->inspect(new NetworkEgressInspectionRequest($profile, Fixture::wiring($profile)));
        $context = ObservabilityContext::uncorrelated();

        $observation = (new NetworkEgressObservationProjector())->project($inspection, $context);
        self::assertSame('kongono.network-egress.inspection', $observation->identity()->name());
        self::assertSame(1, $observation->identity()->schemaVersion());
        self::assertSame(ObservationKind::OperationalEvent, $observation->kind());
        self::assertSame(ObservationStatus::Ok, $observation->status());
        self::assertSame(13, $observation->attributes()->count());

        $audit = (new NetworkEgressSecurityAuditProjector())->project($inspection, $context);
        self::assertSame('kongono.network-egress.inspection.security', $audit->definition()->identity()->name());
        self::assertSame(SecurityAuditOutcome::Succeeded, $audit->outcome());
        self::assertSame(DiagnosticDisclosure::Safe, $audit->disclosure());
        self::assertSame(10, $audit->attributes()->count());
    }
}
