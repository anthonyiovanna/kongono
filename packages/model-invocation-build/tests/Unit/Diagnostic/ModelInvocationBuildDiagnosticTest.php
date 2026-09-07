<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Unit\Diagnostic;

use Kongono\ModelInvocationBuild\Diagnostic\ModelInvocationBuildDiagnostic;
use PHPUnit\Framework\TestCase;

final class ModelInvocationBuildDiagnosticTest extends TestCase
{
    public function testExactClosedIdentifiersAndSafeSchemas(): void
    {
        self::assertSame([
            'KGN-MODEL-BUILD-1410', 'KGN-MODEL-BUILD-1411', 'KGN-MODEL-BUILD-1412',
            'KGN-MODEL-BUILD-1413', 'KGN-MODEL-BUILD-1414', 'KGN-MODEL-BUILD-1415',
            'KGN-MODEL-BUILD-1416',
        ], array_map(static fn(ModelInvocationBuildDiagnostic $diagnostic): string => $diagnostic->value, ModelInvocationBuildDiagnostic::cases()));
        foreach (ModelInvocationBuildDiagnostic::cases() as $diagnostic) {
            self::assertNotSame('', $diagnostic->title());
            self::assertNotSame('', $diagnostic->remediation());
            $context = $diagnostic->context(['violation' => 'unsupported_violation']);
            self::assertSame('unsupported_violation', $context->fields()[0]->value());
        }
    }
}
