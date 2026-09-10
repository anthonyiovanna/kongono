<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Tests\Security;

use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifact;
use Kongono\ExternalServiceInspection\Derivation\ExternalServiceIntelligenceDeriver;
use Kongono\ExternalServiceInspection\Exception\ExternalServiceInspectionBindingException;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspectionRequest;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspector;
use Kongono\ExternalServiceInspection\Projection\ExternalServiceObservationProjector;
use Kongono\ExternalServiceInspection\Projection\ExternalServiceSecurityAuditProjector;
use Kongono\ExternalServiceInspection\Role\ExternalServiceInspectionDeveloperRole;
use Kongono\ExternalServiceInspection\Tests\Support\ExternalServiceInspectionFactory as Fixture;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\Observability\ObservabilityContext;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class ExternalServiceInspectionDisclosureTest extends TestCase
{
    public function testHostileResponseBodyIsNeverAccessedAndRawValuesRemainAbsent(): void
    {
        $body = new ExternalServiceThrowingStream();
        $profile = Fixture::profile();
        $inspection = (new ExternalServiceInspector())->inspect(new ExternalServiceInspectionRequest(
            $profile,
            Fixture::result($profile, EgressOutcome::Succeeded, $body),
        ));
        $view = (new ExternalServiceIntelligenceDeriver())->derive($inspection, ExternalServiceInspectionDeveloperRole::Chie);
        $observation = (new ExternalServiceObservationProjector())->project($inspection, ObservabilityContext::uncorrelated());
        $audit = (new ExternalServiceSecurityAuditProjector())->project($inspection, ObservabilityContext::uncorrelated());

        self::assertSame(0, $body->calls);
        $surfaces = [
            $inspection->canonicalBytes(),
            json_encode($inspection->toArray(), JSON_THROW_ON_ERROR),
            $view->canonicalBytes(),
            json_encode($observation->attributes()->attributes(), JSON_THROW_ON_ERROR),
            json_encode($audit->attributes()->attributes(), JSON_THROW_ON_ERROR),
        ];
        foreach ($surfaces as $surface) {
            foreach ([
                Fixture::RAW_HOST,
                Fixture::RAW_BINDING,
                Fixture::RAW_PROFILE,
                Fixture::RAW_CREDENTIAL_SOURCE,
                Fixture::RAW_CREDENTIAL_REFERENCE,
                Fixture::RAW_OPERATION,
                Fixture::RAW_RECEIPT,
                'attempt.private',
                'private-api',
                'foreign-sensitive-body-message',
            ] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $surface);
            }
        }
    }

    public function testTranslatedFactoryFailureOmitsForeignPresentationAndPreservesCause(): void
    {
        $profile = Fixture::profile();
        $artifact = $profile->bindingArtifact();
        $forgedArtifact = new ExternalServiceBindingArtifact(
            '{"foreign":"sensitive"}',
            $artifact->artifactSha256(),
            $artifact->sourceSha256(),
            $artifact->source(),
        );
        $forged = new ExternalServiceApplicationProfile(
            $profile->bindingSource(),
            $forgedArtifact,
            $profile->binding(),
            $profile->networkEgressProfile(),
            $profile->identity(),
        );

        try {
            (new ExternalServiceInspector())->inspect(new ExternalServiceInspectionRequest($forged));
            self::fail('Expected mismatch.');
        } catch (ExternalServiceInspectionBindingException $exception) {
            self::assertNotNull($exception->getPrevious());
            self::assertStringNotContainsString(Fixture::RAW_BINDING, $exception->getMessage());
            self::assertStringNotContainsString(Fixture::RAW_HOST, (string) $exception);
            self::assertSame(['component'], array_map(static fn($field): string => $field->key(), $exception->diagnosticContext()->fields()));
            self::assertSame('profile_binding', $exception->diagnosticContext()->fields()[0]->value());
        }
    }
}

final class ExternalServiceThrowingStream implements StreamInterface
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
