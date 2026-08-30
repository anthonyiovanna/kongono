<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphBuild\Tests\Unit\Ir;

use Kongono\ApplicationGraphBuild\Exception\InvalidGraphBuildException;
use Kongono\ApplicationGraphBuild\Ir\GraphFieldRecord;
use Kongono\ApplicationGraphContract\Schema\FieldName;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class GraphFieldRecordTest extends TestCase
{
    public function testExactTextGrammarAndImmutability(): void
    {
        foreach (['x', 'é', str_repeat('a', GraphFieldRecord::MAX_VALUE_BYTES)] as $value) {
            $record = new GraphFieldRecord(new FieldName('symbol'), $value);
            self::assertSame('symbol', $record->name()->value());
            self::assertSame($value, $record->value());
        }

        $reflection = new ReflectionClass(GraphFieldRecord::class);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertSame(4096, GraphFieldRecord::MAX_VALUE_BYTES);
    }

    public function testInvalidTextFailsWithoutDisclosure(): void
    {
        foreach (['', str_repeat('x', 4097), "\xC3\x28", "line\nfeed", "\x7F", "\u{0085}"] as $raw) {
            try {
                new GraphFieldRecord(new FieldName('symbol'), $raw);
                self::fail('Invalid text was accepted.');
            } catch (InvalidGraphBuildException $exception) {
                self::assertSame('KGN-GRAPH-1106', $exception->diagnosticId());
                if ('' !== $raw) {
                    self::assertStringNotContainsString($raw, $exception->getMessage());
                    self::assertStringNotContainsString($raw, (string) $exception);
                }
                self::assertStringNotContainsString('symbol', (string) $exception);
            }
        }
    }
}
