<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Unit\Diagnostic;

use Kongono\AgentRuntime\Diagnostic\AgentRuntimeDiagnostic;
use PHPUnit\Framework\TestCase;

final class AgentRuntimeDiagnosticTest extends TestCase
{
    public function testDiagnosticIdentitiesAreClosedAndNonOverlapping(): void
    {
        self::assertSame([
            'KGN-AGENT-RUNTIME-1330', 'KGN-AGENT-RUNTIME-1331', 'KGN-AGENT-RUNTIME-1332', 'KGN-AGENT-RUNTIME-1333',
        ], array_map(static fn(AgentRuntimeDiagnostic $value): string => $value->value, AgentRuntimeDiagnostic::cases()));
    }
}
