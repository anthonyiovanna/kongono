<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Diagnostic;

use Kongono\ConsoleApplication\Diagnostic\ConsoleApplicationDiagnostic;
use PHPUnit\Framework\TestCase;

final class ConsoleApplicationDiagnosticTest extends TestCase
{
    public function testCatalogAndContextsAreExactAndNonDisclosing(): void
    {
        self::assertSame([
            'KGN-CONSOLE-APPLICATION-1001',
            'KGN-CONSOLE-APPLICATION-1002',
            'KGN-CONSOLE-APPLICATION-1003',
        ], array_column(ConsoleApplicationDiagnostic::cases(), 'value'));

        foreach (ConsoleApplicationDiagnostic::cases() as $diagnostic) {
            $context = $diagnostic->context();
            $values = [];
            foreach ($context->fields() as $field) {
                $values[$field->key()] = $field->value();
            }
            self::assertSame('console-application', $values['package']);
            $serialized = json_encode([$context->operation(), $context->violatedInvariant(), $values], JSON_THROW_ON_ERROR);
            self::assertStringNotContainsString('argument', $serialized);
            self::assertStringNotContainsString('exception', $serialized);
        }
    }
}
