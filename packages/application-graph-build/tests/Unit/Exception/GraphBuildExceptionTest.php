<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Tests\Unit\Exception;

use Kongono\ApplicationGraphBuild\Exception\ExceptionInterface;
use Kongono\ApplicationGraphBuild\Exception\InvalidGraphBuildException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class GraphBuildExceptionTest extends TestCase
{
    public function testFactoriesOwnExactFamilyAndPreviousThrowable(): void
    {
        $previous = new RuntimeException('native cause');
        $exceptions = [
            InvalidGraphBuildException::input('fields_not_list', 'node'),
            InvalidGraphBuildException::fieldSet('unknown_field', 'node'),
            InvalidGraphBuildException::duplicate('duplicate_node_identity', 'node'),
            InvalidGraphBuildException::endpoint('missing_source'),
            InvalidGraphBuildException::limit('node_count', 'node', 65537, $previous),
        ];
        self::assertTrue((new \ReflectionClass(InvalidGraphBuildException::class))->implementsInterface(ExceptionInterface::class));
        self::assertSame(['KGN-GRAPH-1106', 'KGN-GRAPH-1107', 'KGN-GRAPH-1108', 'KGN-GRAPH-1109', 'KGN-GRAPH-1110'], array_map(static fn(ExceptionInterface $exception): string => $exception->diagnosticId(), $exceptions));
        self::assertSame($previous, $exceptions[4]->getPrevious());
    }

    public function testUnknownTokensAndRawMaterialNeverPassThrough(): void
    {
        $raw = 'SECRET-field-name-kgn:graph:v1:node:' . str_repeat('a', 64);
        $exception = InvalidGraphBuildException::input($raw, $raw, -5, -9);
        self::assertStringContainsString('unsupported_violation', (string) $exception);
        self::assertStringNotContainsString($raw, $exception->getMessage());
        self::assertStringNotContainsString($raw, (string) $exception);
        foreach ($exception->diagnosticContext()->fields() as $field) {
            self::assertNotSame($raw, $field->value());
        }
    }
}
