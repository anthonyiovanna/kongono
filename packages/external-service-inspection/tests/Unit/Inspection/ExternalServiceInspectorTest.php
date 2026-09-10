<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Tests\Unit\Inspection;

use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileIdentity;
use Kongono\ExternalServiceInspection\Exception\ExternalServiceInspectionBindingException;
use Kongono\ExternalServiceInspection\Exception\ExternalServiceResultEvidenceException;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspection;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspectionRequest;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspector;
use Kongono\ExternalServiceInspection\Tests\Support\ExternalServiceInspectionFactory as Fixture;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExternalServiceInspectorTest extends TestCase
{
    public function testExactProfileProducesDeterministicBoundedInspectionWithoutResult(): void
    {
        $profile = Fixture::profile();
        $request = new ExternalServiceInspectionRequest($profile);
        $first = (new ExternalServiceInspector())->inspect($request);
        $second = (new ExternalServiceInspector())->inspect($request);

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->identity(), $second->identity());
        self::assertSame(ExternalServiceInspection::SCHEMA, $first->toArray()['schema']);
        self::assertSame(1, $first->bindingCount());
        self::assertSame(2, $first->requiredCapabilityCount());
        self::assertSame(1, $first->allowedOriginCount());
        self::assertFalse($first->resultSupplied());
        self::assertSame([], $first->toArray()['completed_result_summary']);
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertFalse(str_contains(substr($first->canonicalBytes(), 0, -1), "\n"));
    }

    public function testForgedProfileIdentityFailsClosedAfterPublicFactoryReconstruction(): void
    {
        $profile = Fixture::profile();
        $forged = new ExternalServiceApplicationProfile(
            $profile->bindingSource(),
            $profile->bindingArtifact(),
            $profile->binding(),
            $profile->networkEgressProfile(),
            new ExternalServiceApplicationProfileIdentity(str_repeat('0', 64)),
        );

        $this->expectException(ExternalServiceInspectionBindingException::class);
        (new ExternalServiceInspector())->inspect(new ExternalServiceInspectionRequest($forged));
    }

    public function testResultBindingMustMatchSelectedProfileBinding(): void
    {
        $profile = Fixture::profile('first');
        $other = Fixture::profile('second');

        $this->expectException(ExternalServiceResultEvidenceException::class);
        (new ExternalServiceInspector())->inspect(new ExternalServiceInspectionRequest($profile, Fixture::result($other)));
    }

    public function testRuntimeCountersMustRemainWithinNestedPolicyBudget(): void
    {
        $profile = Fixture::profile();

        $this->expectException(ExternalServiceResultEvidenceException::class);
        (new ExternalServiceInspector())->inspect(new ExternalServiceInspectionRequest(
            $profile,
            Fixture::result($profile, elapsedMilliseconds: Fixture::budget()->totalTimeoutMilliseconds() + 1),
        ));
    }

    #[DataProvider('outcomes')]
    public function testAllSixOutcomesAndUncertaintyRemainExact(EgressOutcome $outcome): void
    {
        $profile = Fixture::profile();
        $inspection = (new ExternalServiceInspector())->inspect(new ExternalServiceInspectionRequest($profile, Fixture::result($profile, $outcome)));

        self::assertTrue($inspection->resultSupplied());
        self::assertSame($outcome->value, $inspection->serviceOutcome()?->value);
        self::assertSame($outcome, $inspection->egressOutcome());
        self::assertTrue($inspection->credentialCleanupComplete());
        self::assertSame(1, $inspection->resultCounts()['credential_acquisition_attempts']);
        self::assertSame(1, $inspection->resultCounts()['credential_material_access_attempts']);
        self::assertSame(1, $inspection->resultCounts()['credential_retirement_attempts']);
        self::assertSame(
            in_array($outcome, [EgressOutcome::CancelledAfterPossibleEffect, EgressOutcome::UncertainEffect], true),
            $inspection->possibleEffectUncertainty(),
        );
        $evidence = $inspection->toArray();
        self::assertIsArray($evidence['limitations']);
        self::assertSame('not-authorized-or-performed', $evidence['limitations']['retry']);
        self::assertSame('not-established', $evidence['limitations']['network_provider_effect']);
    }

    /** @return list<array{EgressOutcome}> */
    public static function outcomes(): array
    {
        return array_map(static fn(EgressOutcome $outcome): array => [$outcome], EgressOutcome::cases());
    }
}
