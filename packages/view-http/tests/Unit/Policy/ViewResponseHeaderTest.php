<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Tests\Unit\Policy;

use Kongono\ViewHttp\Exception\InvalidViewHttpPolicyException;
use Kongono\ViewHttp\Policy\ViewResponseHeader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ViewResponseHeaderTest extends TestCase
{
    public function testValidHeaderPreservesExactSpellingOrderAndValues(): void
    {
        $header = new ViewResponseHeader('X-View-Evidence', ['one', "two\tthree"]);
        self::assertSame('X-View-Evidence', $header->name());
        self::assertSame(['one', "two\tthree"], $header->values());
    }

    /** @param array<array-key, mixed> $values */
    #[DataProvider('invalidHeaders')]
    public function testInvalidHeadersFailClosed(string $name, array $values): void
    {
        $this->expectException(InvalidViewHttpPolicyException::class);
        new ViewResponseHeader($name, $values);
    }

    /** @return iterable<string, array{string, array<array-key, mixed>}> */
    public static function invalidHeaders(): iterable
    {
        yield 'empty name' => ['', ['x']];
        yield 'long name' => [str_repeat('a', 65), ['x']];
        yield 'bad grammar' => ['Bad Header', ['x']];
        yield 'reserved content type' => ['Content-Type', ['x']];
        yield 'reserved length' => ['content-length', ['x']];
        yield 'reserved connection' => ['Connection', ['x']];
        yield 'no values' => ['X-Test', []];
        yield 'too many values' => ['X-Test', array_fill(0, 9, 'x')];
        yield 'long value' => ['X-Test', [str_repeat('x', 1025)]];
        yield 'carriage return' => ['X-Test', ["x\ry"]];
        yield 'line feed' => ['X-Test', ["x\ny"]];
        yield 'nul' => ['X-Test', ["x\0y"]];
        yield 'non ascii' => ['X-Test', ['café']];
    }

    public function testBoundaryLengthsAreAccepted(): void
    {
        $header = new ViewResponseHeader(str_repeat('A', 64), array_fill(0, 8, str_repeat('x', 1024)));
        self::assertSame(64, strlen($header->name()));
        self::assertCount(8, $header->values());
        foreach ($header->values() as $value) {
            self::assertSame(1024, strlen($value));
        }
    }

    public function testEveryHeaderMaximumAcceptsTheValueImmediatelyBelow(): void
    {
        $header = new ViewResponseHeader(str_repeat('A', 63), array_fill(0, 7, str_repeat('x', 1023)));
        self::assertSame(63, strlen($header->name()));
        self::assertCount(7, $header->values());
        foreach ($header->values() as $value) {
            self::assertSame(1023, strlen($value));
        }
    }
}
