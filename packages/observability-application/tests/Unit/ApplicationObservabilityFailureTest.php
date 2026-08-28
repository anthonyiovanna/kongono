<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Unit;

use Kongono\Metrics\Exception\MetricRecordingException;
use Kongono\Metrics\MetricMeasurement;
use Kongono\Metrics\MetricNumber;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservationStatus;
use Kongono\ObservabilityApplication\ApplicationObservabilityOperation;
use Kongono\ObservabilityApplication\ApplicationObservabilityOperationState;
use Kongono\ObservabilityApplication\ApplicationObservabilityWork;
use Kongono\ObservabilityApplication\Exception\ApplicationObservabilityCleanupException;
use Kongono\ObservabilityApplication\Exception\ApplicationObservabilityLifecycleException;
use Kongono\ObservabilityApplication\Tests\Fixture\ObservabilityApplicationFixture;
use Kongono\ObservabilityApplication\Tests\Fixture\TestOperationEvidence;
use Kongono\ObservabilityApplication\Tests\Fixture\TestOtlpTransport;
use Kongono\OpenTelemetry\Exception\OpenTelemetryExportException;
use Kongono\OperationalEvents\OperationalEvent;
use Kongono\OperationalEvents\OperationalEventDefinition;
use Kongono\OperationalEvents\OperationalEventSchema;
use Kongono\OperationalEvents\OperationalEventSeverity;
use Kongono\Tracing\Exception\TraceRecordingException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApplicationObservabilityFailureTest extends TestCase
{
    public function testPrimaryFailureIsRethrownByExactIdentityAfterSuccessfulCleanup(): void
    {
        [$application] = ObservabilityApplicationFixture::application();
        $primary = new RuntimeException('private-work-failure');
        $evidence = new TestOperationEvidence();
        $work = new /** @implements ApplicationObservabilityWork<mixed> */ class ($primary, $evidence) implements ApplicationObservabilityWork {
            public function __construct(
                private readonly RuntimeException $failure,
                private readonly TestOperationEvidence $evidence,
            ) {}

            public function execute(ApplicationObservabilityOperation $operation): mixed
            {
                $this->evidence->operation = $operation;
                throw $this->failure;
            }
        };

        try {
            $application->execute(ObservabilityApplicationFixture::plan(), $work);
            self::fail('Expected exact primary failure.');
        } catch (RuntimeException $failure) {
            self::assertSame($primary, $failure);
        }
        self::assertInstanceOf(ApplicationObservabilityOperation::class, $evidence->operation);
        self::assertSame(ApplicationObservabilityOperationState::Closed, $evidence->operation->state());
        $application->close();
    }

    public function testPrimaryAndFlushFailureArePreservedWithoutDisclosure(): void
    {
        $transport = new TestOtlpTransport();
        $transportFailure = new RuntimeException('private-transport-failure');
        $transport->flushFailure = $transportFailure;
        [$application] = ObservabilityApplicationFixture::application(64, $transport);
        $primary = new RuntimeException('private-primary-failure');
        $work = new /** @implements ApplicationObservabilityWork<mixed> */ class ($primary) implements ApplicationObservabilityWork {
            public function __construct(private readonly RuntimeException $failure) {}
            public function execute(ApplicationObservabilityOperation $operation): mixed
            {
                unset($operation);
                throw $this->failure;
            }
        };

        try {
            $application->execute(ObservabilityApplicationFixture::plan(), $work);
            self::fail('Expected combined failure.');
        } catch (ApplicationObservabilityCleanupException $failure) {
            self::assertSame($primary, $failure->primaryFailure());
            self::assertCount(1, $failure->cleanupFailures());
            self::assertInstanceOf(OpenTelemetryExportException::class, $failure->cleanupFailures()[0]);
            self::assertSame($transportFailure, $failure->cleanupFailures()[0]->getPrevious());
            self::assertStringNotContainsString('private-primary-failure', (string) $failure);
            self::assertStringNotContainsString('private-transport-failure', (string) $failure);
        }

        $transport->flushFailure = null;
        $application->close();
    }

    public function testMetricThenTraceCleanupFailuresRetainDeterministicOrder(): void
    {
        [$application] = ObservabilityApplicationFixture::application(1);
        $plan = ObservabilityApplicationFixture::plan();
        $work = new /** @implements ApplicationObservabilityWork<mixed> */ class ($plan) implements ApplicationObservabilityWork {
            public function __construct(private readonly \Kongono\ObservabilityApplication\ApplicationObservabilityPlan $plan) {}

            public function execute(ApplicationObservabilityOperation $operation): mixed
            {
                $operation->recordOperationalEvent(new OperationalEvent(
                    $this->plan->context(),
                    new OperationalEventDefinition($this->plan->rootTraceIdentity(), OperationalEventSeverity::Info, new OperationalEventSchema([])),
                    ObservationStatus::Ok,
                    new AttributeSet([]),
                ));
                $instrument = $this->plan->metricPlan()->instruments()[0];
                $operation->metricCollection()->record($instrument, new MetricMeasurement(new MetricNumber(1), new AttributeSet([])));

                return new \stdClass();
            }
        };

        try {
            $application->execute($plan, $work);
            self::fail('Expected cleanup failures.');
        } catch (ApplicationObservabilityCleanupException $failure) {
            self::assertNull($failure->primaryFailure());
            self::assertCount(2, $failure->cleanupFailures());
            self::assertInstanceOf(MetricRecordingException::class, $failure->cleanupFailures()[0]);
            self::assertInstanceOf(TraceRecordingException::class, $failure->cleanupFailures()[1]);
        }
        $application->close();
    }

    public function testCloseIsTerminalAndIdempotentAfterTransportFailure(): void
    {
        $transport = new TestOtlpTransport();
        $closeFailure = new RuntimeException('private-close-failure');
        $transport->closeFailure = $closeFailure;
        [$application] = ObservabilityApplicationFixture::application(64, $transport);

        try {
            $application->close();
            self::fail('Expected close failure.');
        } catch (OpenTelemetryExportException $failure) {
            self::assertSame([$closeFailure], $failure->failures());
        }
        self::assertSame(1, $transport->closes);
        $application->close();
        self::assertSame(1, $transport->closes);
        foreach ([
            static fn() => $application->flush(),
            static fn() => $application->execute(ObservabilityApplicationFixture::plan(), new /** @implements ApplicationObservabilityWork<mixed> */ class implements ApplicationObservabilityWork {
                public function execute(ApplicationObservabilityOperation $operation): mixed
                {
                    unset($operation);

                    return null;
                }
            }),
        ] as $closedCall) {
            try {
                $closedCall();
                self::fail('Expected closed application rejection.');
            } catch (ApplicationObservabilityLifecycleException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
