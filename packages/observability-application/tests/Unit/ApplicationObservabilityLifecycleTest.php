<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Unit;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Metrics\MetricMeasurement;
use Kongono\Metrics\MetricNumber;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;
use Kongono\ObservabilityApplication\ApplicationObservability;
use Kongono\ObservabilityApplication\ApplicationObservabilityOperation;
use Kongono\ObservabilityApplication\ApplicationObservabilityOperationState;
use Kongono\ObservabilityApplication\ApplicationObservabilityWork;
use Kongono\ObservabilityApplication\Exception\ApplicationObservabilityLifecycleException;
use Kongono\ObservabilityApplication\Tests\Fixture\ObservabilityApplicationFixture;
use Kongono\ObservabilityApplication\Tests\Fixture\TestOperationEvidence;
use Kongono\OperationalEvents\OperationalEvent;
use Kongono\OperationalEvents\OperationalEventDefinition;
use Kongono\OperationalEvents\OperationalEventSchema;
use Kongono\OperationalEvents\OperationalEventSeverity;
use Kongono\SecurityAudit\SecurityAuditDefinition;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\SecurityAudit\SecurityAuditField;
use Kongono\SecurityAudit\SecurityAuditOutcome;
use Kongono\SecurityAudit\SecurityAuditRetentionIntent;
use Kongono\SecurityAudit\SecurityAuditSchema;
use PHPUnit\Framework\TestCase;

final class ApplicationObservabilityLifecycleTest extends TestCase
{
    public function testSuccessUsesExactOperationRecordsDistinctSignalsFlushesAndReleases(): void
    {
        [$application, $transport] = ObservabilityApplicationFixture::application();
        $plan = ObservabilityApplicationFixture::plan();
        $result = new \stdClass();
        $evidence = new TestOperationEvidence();
        $work = new /** @implements ApplicationObservabilityWork<mixed> */ class ($plan, $result, $evidence) implements ApplicationObservabilityWork {
            public function __construct(
                private readonly \Kongono\ObservabilityApplication\ApplicationObservabilityPlan $plan,
                private readonly object $result,
                private readonly TestOperationEvidence $evidence,
            ) {}

            public function execute(ApplicationObservabilityOperation $operation): mixed
            {
                ++$this->evidence->calls;
                $this->evidence->operation = $operation;
                TestCase::assertSame(ApplicationObservabilityOperationState::Active, $operation->state());
                $instrument = $this->plan->metricPlan()->instruments()[0];
                $operation->metricCollection()->record(
                    $instrument,
                    new MetricMeasurement(new MetricNumber(1), new AttributeSet([])),
                );
                $operation->recordOperationalEvent(new OperationalEvent(
                    $this->plan->context(),
                    new OperationalEventDefinition($this->plan->rootTraceIdentity(), OperationalEventSeverity::Info, new OperationalEventSchema([])),
                    ObservationStatus::Ok,
                    new AttributeSet([]),
                ));
                $auditKey = new AttributeKey('app.audit.kind');
                $auditValue = AttributeValue::text('allowed');
                $operation->recordSecurityAudit(new SecurityAuditEvidence(
                    $this->plan->context(),
                    new SecurityAuditDefinition(
                        $this->plan->rootTraceIdentity(),
                        SecurityAuditRetentionIntent::Short,
                        new SecurityAuditSchema([new SecurityAuditField($auditKey, AttributeValueKind::Text, DiagnosticDisclosure::Safe, true, [$auditValue])]),
                    ),
                    SecurityAuditOutcome::Succeeded,
                    new AttributeSet([new SignalAttribute($auditKey, $auditValue)]),
                ));

                return $this->result;
            }
        };

        self::assertSame($result, $application->execute($plan, $work));
        self::assertSame(1, $evidence->calls);
        self::assertInstanceOf(ApplicationObservabilityOperation::class, $evidence->operation);
        $captured = $evidence->operation;
        self::assertSame(ApplicationObservabilityOperationState::Closed, $captured->state());
        self::assertSame(1, $transport->flushes);
        self::assertSame(['Logs', 'Logs', 'Metrics', 'Traces'], array_map(static fn($signal): string => $signal->name, $transport->signals));
        $this->expectLifecycleFailure(static fn() => $captured->traceOperation());
        $this->expectLifecycleFailure(static fn() => $captured->metricCollection());
    }

    public function testOperationRejectsForeignContextAndApplicationFlushAndCloseWhileActive(): void
    {
        [$application, $transport] = ObservabilityApplicationFixture::application();
        $plan = ObservabilityApplicationFixture::plan();
        $checks = 0;
        $work = new /** @implements ApplicationObservabilityWork<mixed> */ class ($application, $plan, $checks) implements ApplicationObservabilityWork {
            public function __construct(
                private readonly ApplicationObservability $application,
                private readonly \Kongono\ObservabilityApplication\ApplicationObservabilityPlan $plan,
                private int &$checks,
            ) {}

            public function execute(ApplicationObservabilityOperation $operation): mixed
            {
                foreach ([
                    fn() => $this->application->flush(),
                    fn() => $this->application->close(),
                    fn() => $operation->recordOperationalEvent(new OperationalEvent(
                        \Kongono\Observability\ObservabilityContext::uncorrelated(),
                        new OperationalEventDefinition($this->plan->rootTraceIdentity(), OperationalEventSeverity::Info, new OperationalEventSchema([])),
                        ObservationStatus::Ok,
                        new AttributeSet([]),
                    )),
                ] as $invalid) {
                    try {
                        $invalid();
                        TestCase::fail('Expected lifecycle rejection.');
                    } catch (ApplicationObservabilityLifecycleException) {
                        ++$this->checks;
                    }
                }

                return null;
            }
        };

        $application->execute($plan, $work);
        self::assertSame(3, $checks);
        self::assertSame(1, $transport->flushes);
        $application->flush();
        self::assertSame(2, $transport->flushes);
        $application->close();
    }

    public function testSixtyFourActiveOperationBoundReleasesAfterRecursiveFailure(): void
    {
        [$application] = ObservabilityApplicationFixture::application(256);
        $plan = ObservabilityApplicationFixture::plan();
        $work = new /** @implements ApplicationObservabilityWork<mixed> */ class ($application, $plan) implements ApplicationObservabilityWork {
            private int $depth = 0;

            public function __construct(
                private readonly ApplicationObservability $application,
                private readonly \Kongono\ObservabilityApplication\ApplicationObservabilityPlan $plan,
            ) {}

            public function execute(ApplicationObservabilityOperation $operation): mixed
            {
                unset($operation);
                ++$this->depth;
                try {
                    return $this->application->execute($this->plan, $this);
                } finally {
                    --$this->depth;
                }
            }
        };

        try {
            $application->execute($plan, $work);
            self::fail('Expected operation capacity failure.');
        } catch (ApplicationObservabilityLifecycleException $failure) {
            $stages = array_values(array_filter(
                $failure->diagnosticContext()->fields(),
                static fn($field): bool => 'stage' === $field->key(),
            ));
            self::assertCount(1, $stages);
            self::assertSame('operation_capacity', $stages[0]->value());
        }

        $result = new \stdClass();
        self::assertSame($result, $application->execute($plan, new /** @implements ApplicationObservabilityWork<mixed> */ class ($result) implements ApplicationObservabilityWork {
            public function __construct(private readonly object $result) {}
            public function execute(ApplicationObservabilityOperation $operation): mixed
            {
                unset($operation);

                return $this->result;
            }
        }));
        $application->close();
    }

    private function expectLifecycleFailure(callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected application observability lifecycle failure.');
        } catch (ApplicationObservabilityLifecycleException) {
            self::addToAssertionCount(1);
        }
    }
}
