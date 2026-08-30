<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Tests\Unit\Exception;

use Kongono\ApplicationGraphContract\Exception\ExceptionInterface;
use Kongono\ApplicationGraphContract\Exception\InvalidGraphContractException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class GraphContractExceptionTest extends TestCase
{
    public function testFactoriesPairExactDiagnosticsAndRetainAnOptionalCause(): void
    {
        $cause = new RuntimeException('bounded cause');
        $exceptions = [
            InvalidGraphContractException::identity('digest_grammar'),
            InvalidGraphContractException::fieldName('field_name_grammar'),
            InvalidGraphContractException::schema('fields_not_list', 2, 1),
            InvalidGraphContractException::provenance('source_digest_grammar'),
            InvalidGraphContractException::limit('field_count', 33, $cause),
        ];

        self::assertSame([
            'KGN-GRAPH-1101',
            'KGN-GRAPH-1102',
            'KGN-GRAPH-1103',
            'KGN-GRAPH-1104',
            'KGN-GRAPH-1105',
        ], array_map(static fn(ExceptionInterface $exception): string => $exception->diagnosticId(), $exceptions));
        self::assertSame($cause, $exceptions[4]->getPrevious());
    }

    public function testMessagesContextsAndStringificationNeverContainCallerValues(): void
    {
        $raw = 'TOP-SECRET-/workspace/customer.php';
        $exception = InvalidGraphContractException::schema($raw, -9, -4);

        self::assertStringNotContainsString($raw, $exception->getMessage());
        self::assertStringNotContainsString($raw, (string) $exception);
        self::assertStringContainsString('unsupported_violation', (string) $exception);
        foreach ($exception->diagnosticContext()->fields() as $field) {
            self::assertNotSame($raw, $field->value());
        }
    }
}
