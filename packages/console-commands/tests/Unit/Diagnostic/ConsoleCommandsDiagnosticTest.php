<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Tests\Unit\Diagnostic;

use Kongono\ConsoleCommands\Diagnostic\ConsoleCommandsDiagnostic;
use PHPUnit\Framework\TestCase;

final class ConsoleCommandsDiagnosticTest extends TestCase
{
    public function testClosedIdentifiersAndSafeContextsAreExact(): void
    {
        self::assertSame([
            'KGN-CONSOLE-COMMANDS-1001',
            'KGN-CONSOLE-COMMANDS-1002',
            'KGN-CONSOLE-COMMANDS-1003',
        ], array_map(static fn(ConsoleCommandsDiagnostic $diagnostic): string => $diagnostic->value, ConsoleCommandsDiagnostic::cases()));

        foreach (ConsoleCommandsDiagnostic::cases() as $diagnostic) {
            $context = $diagnostic->context();
            self::assertSame(['package', 'operation_category', 'violated_invariant', 'remediation'], array_map(static fn($field): string => $field->key(), $context->fields()));
            foreach ($context->fields() as $field) {
                self::assertSame('safe', $field->disclosure()->value);
            }
            $serialized = json_encode($context, JSON_THROW_ON_ERROR);
            foreach (['secret-input', 'node-key', 'service-name', 'check-reason', 'C:\\path', 'exception-text'] as $sentinel) {
                self::assertStringNotContainsString($sentinel, $serialized);
            }
        }
    }
}
