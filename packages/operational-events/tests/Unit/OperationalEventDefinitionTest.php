<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Tests\Unit;

use Kongono\Observability\SignalIdentity;
use Kongono\OperationalEvents\OperationalEventDefinition;
use Kongono\OperationalEvents\OperationalEventSchema;
use Kongono\OperationalEvents\OperationalEventSeverity;
use PHPUnit\Framework\TestCase;

final class OperationalEventDefinitionTest extends TestCase
{
    public function testDefinitionAndSeverityAreExact(): void
    {
        self::assertSame(['debug', 'info', 'notice', 'warning', 'error', 'critical'], array_column(OperationalEventSeverity::cases(), 'value'));
        $identity = new SignalIdentity('operations.degraded', 2);
        $schema = new OperationalEventSchema([]);
        $definition = new OperationalEventDefinition($identity, OperationalEventSeverity::Warning, $schema);
        self::assertSame($identity, $definition->identity());
        self::assertSame(OperationalEventSeverity::Warning, $definition->severity());
        self::assertSame($schema, $definition->schema());
    }
}
