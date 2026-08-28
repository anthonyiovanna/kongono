<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Tests\Unit;

use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalIdentity;
use Kongono\OperationalEvents\Exception\OperationalEventRecordingException;
use Kongono\OperationalEvents\OperationalEvent;
use Kongono\OperationalEvents\OperationalEventDefinition;
use Kongono\OperationalEvents\OperationalEventRuntime;
use Kongono\OperationalEvents\OperationalEventSchema;
use Kongono\OperationalEvents\OperationalEventSeverity;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OperationalEventFailureTest extends TestCase
{
    public function testRecorderFailureIsWrappedOnceAndNeverRetried(): void
    {
        $failure = new RuntimeException('protected-recorder-message');
        $recorder = new class ($failure) implements ObservationRecorder {
            public int $calls = 0;
            public function __construct(private RuntimeException $failure) {}
            public function record(Observation $observation): void
            {
                ++$this->calls;
                throw $this->failure;
            }
        };
        $runtime = new OperationalEventRuntime($recorder);
        $event = new OperationalEvent(ObservabilityContext::uncorrelated(), new OperationalEventDefinition(new SignalIdentity('operations.failure', 1), OperationalEventSeverity::Error, new OperationalEventSchema([])), ObservationStatus::Error, new AttributeSet([]));
        try {
            $runtime->record($event);
            self::fail('Expected recording failure.');
        } catch (OperationalEventRecordingException $exception) {
            self::assertSame($failure, $exception->getPrevious());
            self::assertStringNotContainsString('protected-recorder-message', (string) $exception);
        }
        self::assertSame(1, $recorder->calls);
    }
}
