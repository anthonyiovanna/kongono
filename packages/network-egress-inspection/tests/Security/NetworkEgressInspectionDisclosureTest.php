<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Tests\Security;

use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressInspection\Derivation\NetworkEgressIntelligenceDeriver;
use Kongono\NetworkEgressInspection\Exception\NetworkEgressInspectionBindingException;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspectionRequest;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspector;
use Kongono\NetworkEgressInspection\Projection\NetworkEgressObservationProjector;
use Kongono\NetworkEgressInspection\Projection\NetworkEgressSecurityAuditProjector;
use Kongono\NetworkEgressInspection\Role\NetworkEgressInspectionDeveloperRole;
use Kongono\NetworkEgressInspection\Tests\Support\NetworkEgressInspectionFactory as Fixture;
use Kongono\Observability\ObservabilityContext;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class NetworkEgressInspectionDisclosureTest extends TestCase
{
    public function testRequestAndResponseBodiesAreNeverAccessedOrDisclosed(): void
    {
        $requestBody = new ThrowingStream();
        $responseBody = new ThrowingStream();
        $profile = Fixture::profile();
        $operation = Fixture::operation($profile->source(), $requestBody);
        $runtime = Fixture::runtime($operation, EgressOutcome::Succeeded, $responseBody);

        $inspection = (new NetworkEgressInspector())->inspect(new NetworkEgressInspectionRequest(
            $profile,
            Fixture::wiring($profile),
            $operation,
            $runtime,
        ));
        $view = (new NetworkEgressIntelligenceDeriver())->derive($inspection, NetworkEgressInspectionDeveloperRole::Chie);
        $observation = (new NetworkEgressObservationProjector())->project($inspection, ObservabilityContext::uncorrelated());
        $audit = (new NetworkEgressSecurityAuditProjector())->project($inspection, ObservabilityContext::uncorrelated());

        self::assertSame(0, $requestBody->calls);
        self::assertSame(0, $responseBody->calls);

        $surfaces = [
            $inspection->canonicalBytes(),
            json_encode($inspection->toArray(), JSON_THROW_ON_ERROR),
            $view->canonicalBytes(),
            json_encode($observation->attributes()->attributes(), JSON_THROW_ON_ERROR),
            json_encode($audit->attributes()->attributes(), JSON_THROW_ON_ERROR),
        ];
        foreach ($surfaces as $surface) {
            foreach ([Fixture::RAW_HOST, Fixture::RAW_URI, Fixture::RAW_POLICY, Fixture::RAW_OPERATION, Fixture::RAW_FAILURE, 'credential=secret', 'private-authority-reference', 'private-attempt-identity', 'private-receipt-identity'] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $surface);
            }
        }
    }

    public function testForeignFailurePresentationIsOmittedWhilePreviousIdentityIsPreserved(): void
    {
        $profile = Fixture::profile(Fixture::source('first'));
        $other = Fixture::profile(Fixture::source('second'));

        try {
            (new NetworkEgressInspector())->inspect(new NetworkEgressInspectionRequest($profile, Fixture::wiring($other)));
            self::fail('Expected mismatch.');
        } catch (NetworkEgressInspectionBindingException $exception) {
            self::assertNotNull($exception->getPrevious());
            self::assertStringNotContainsString('first.example', $exception->getMessage());
            self::assertStringNotContainsString('second.example', (string) $exception);
            self::assertSame(['component'], array_map(static fn($field): string => $field->key(), $exception->diagnosticContext()->fields()));
            self::assertSame('profile_wiring', $exception->diagnosticContext()->fields()[0]->value());
        }
    }
}

final class ThrowingStream implements StreamInterface
{
    public int $calls = 0;

    public function __toString(): string
    {
        return $this->fail();
    }

    public function close(): void
    {
        $this->fail();
    }

    public function detach()
    {
        return $this->fail();
    }

    public function getSize(): ?int
    {
        return $this->fail();
    }

    public function tell(): int
    {
        return $this->fail();
    }

    public function eof(): bool
    {
        return $this->fail();
    }

    public function isSeekable(): bool
    {
        return $this->fail();
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->fail();
    }

    public function rewind(): void
    {
        $this->fail();
    }

    public function isWritable(): bool
    {
        return $this->fail();
    }

    public function write(string $string): int
    {
        return $this->fail();
    }

    public function isReadable(): bool
    {
        return $this->fail();
    }

    public function read(int $length): string
    {
        return $this->fail();
    }

    public function getContents(): string
    {
        return $this->fail();
    }

    public function getMetadata(?string $key = null)
    {
        return $this->fail();
    }

    private function fail(): never
    {
        ++$this->calls;
        throw new RuntimeException('foreign-sensitive-body-message');
    }
}
