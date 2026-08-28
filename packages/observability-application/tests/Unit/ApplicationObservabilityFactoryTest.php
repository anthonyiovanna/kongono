<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Unit;

use Kongono\ObservabilityApplication\ApplicationObservabilityFactory;
use Kongono\ObservabilityApplication\ApplicationObservabilityOperation;
use Kongono\ObservabilityApplication\ApplicationObservabilityWork;
use Kongono\ObservabilityApplication\Tests\Fixture\ObservabilityApplicationFixture;
use Kongono\ObservabilityApplication\Tests\Fixture\TestOpenTelemetryClock;
use Kongono\ObservabilityApplication\Tests\Fixture\TestOtlpTransport;
use Kongono\ObservabilityApplication\Tests\Fixture\TestTraceClock;
use Kongono\ObservabilityApplication\Tests\Fixture\TestTraceIdentifierSource;
use Kongono\ObservabilityApplication\Tests\Fixture\TestTraceSampler;
use Kongono\ObservabilityBuild\Build\ObservabilityArtifact;
use Kongono\ObservabilityBuild\Workflow\CurrentObservabilityConfiguration;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryResource;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use PHPUnit\Framework\TestCase;

final class ApplicationObservabilityFactoryTest extends TestCase
{
    public function testFactoryUsesExactCurrentConfigurationAndCollaborators(): void
    {
        $resource = new OpenTelemetryResource('factory-test', '1.0.0', 'test');
        $batch = new OpenTelemetryBatchPolicy(8, 4, 321);
        $artifact = new ObservabilityArtifact(
            "{}\n",
            str_repeat('a', 64),
            str_repeat('b', 64),
            str_repeat('c', 64),
            str_repeat('d', 64),
            'kongono.opentelemetry.otlp-http-protobuf.v1',
            $resource,
            $batch,
        );
        $configuration = new CurrentObservabilityConfiguration($artifact, 'kongono-observability-v1-test.json');
        $transport = new TestOtlpTransport();
        $application = (new ApplicationObservabilityFactory())->create(
            $configuration,
            new TestTraceClock(),
            new TestTraceIdentifierSource(),
            new TestTraceSampler(),
            new OpenTelemetryTimeAnchor(0, 1),
            new TestOpenTelemetryClock(),
            $transport,
        );
        $result = new \stdClass();

        self::assertSame($result, $application->execute(ObservabilityApplicationFixture::plan(), new /** @implements ApplicationObservabilityWork<mixed> */ class ($result) implements ApplicationObservabilityWork {
            public function __construct(private readonly object $result) {}
            public function execute(ApplicationObservabilityOperation $operation): mixed
            {
                unset($operation);

                return $this->result;
            }
        }));
        self::assertSame(1, $transport->flushes);
        self::assertSame(['Traces'], array_map(static fn($signal): string => $signal->name, $transport->signals));
        $application->close();
        self::assertSame(1, $transport->closes);
    }
}
