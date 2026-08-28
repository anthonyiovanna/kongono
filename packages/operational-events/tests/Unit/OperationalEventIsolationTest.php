<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Tests\Unit;

use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalIdentity;
use Kongono\OperationalEvents\OperationalEvent;
use Kongono\OperationalEvents\OperationalEventDefinition;
use Kongono\OperationalEvents\OperationalEventRuntime;
use Kongono\OperationalEvents\OperationalEventSchema;
use Kongono\OperationalEvents\OperationalEventSeverity;
use PHPUnit\Framework\TestCase;

final class OperationalEventIsolationTest extends TestCase
{
    public function testSequentialOverlappingAndReentrantCallsShareNoRecordState(): void
    {
        $recorder = new class implements ObservationRecorder {
            public ?OperationalEventRuntime $runtime = null;
            public ?OperationalEvent $nested = null;
            /** @var list<string> */
            public array $names = [];
            public function record(Observation $observation): void
            {
                $this->names[] = $observation->identity()->name();
                if (1 === count($this->names)) {
                    $this->runtime?->record($this->nested ?? throw new \LogicException());
                }
            }
        };
        $runtime = new OperationalEventRuntime($recorder);
        $recorder->runtime = $runtime;
        $recorder->nested = $this->event('operations.nested');
        $runtime->record($this->event('operations.outer'));
        $runtime->record($this->event('operations.after'));
        self::assertSame(['operations.outer', 'operations.nested', 'operations.after'], $recorder->names);
    }

    private function event(string $name): OperationalEvent
    {
        return new OperationalEvent(ObservabilityContext::uncorrelated(), new OperationalEventDefinition(new SignalIdentity($name, 1), OperationalEventSeverity::Notice, new OperationalEventSchema([])), ObservationStatus::Ok, new AttributeSet([]));
    }
}
