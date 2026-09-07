<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Tests\Unit\Diagnostic;

use Kongono\ModelInvocationAgentBridge\Diagnostic\ModelInvocationAgentBridgeDiagnostic;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ModelInvocationAgentBridgeDiagnostic::class)]
final class ModelInvocationAgentBridgeDiagnosticTest extends TestCase
{
    public function testTheClosedCatalogOwnsExactIdsAndSafeSchemas(): void
    {
        self::assertSame([
            'KGN-MODEL-BRIDGE-1425',
            'KGN-MODEL-BRIDGE-1426',
            'KGN-MODEL-BRIDGE-1427',
            'KGN-MODEL-BRIDGE-1428',
        ], array_map(static fn(ModelInvocationAgentBridgeDiagnostic $diagnostic): string => $diagnostic->value, ModelInvocationAgentBridgeDiagnostic::cases()));

        foreach (ModelInvocationAgentBridgeDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            self::assertSame('violation', $diagnostic->contextSchema()->fields()[0]->key());
            self::assertSame('stage', $diagnostic->contextSchema()->fields()[1]->key());
        }
    }
}
