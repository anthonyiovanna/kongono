<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Render;

use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Render\RenderedOutput;
use Kongono\ViewContract\Render\RenderLimits;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RenderedOutputTest extends TestCase
{
    public function testExactUtf8BytesIncludingEmptyTabAndLfArePreserved(): void
    {
        $limits = new RenderLimits(64, 1);
        $bytes = "<p>caf\u{00E9}\t\n</p>";
        $output = new RenderedOutput($bytes, $limits);
        self::assertSame($bytes, $output->bytes());
        self::assertSame(strlen($bytes), $output->byteCount());
        self::assertTrue($output->equals(new RenderedOutput($bytes, $limits)));
        self::assertSame('', (new RenderedOutput('', $limits))->bytes());
    }

    /** @return iterable<string, array{string, int}> */
    public static function invalidOutput(): iterable
    {
        yield 'over limit' => ['ab', 1];
        yield 'invalid utf8' => ["\xC3\x28", 16];
        yield 'leading bom' => ["\xEF\xBB\xBFvalue", 16];
        yield 'nul' => ["a\0b", 16];
        yield 'carriage return' => ["a\rb", 16];
    }

    #[DataProvider('invalidOutput')]
    public function testInvalidOutputFailsClosed(string $bytes, int $maximum): void
    {
        $this->expectException(InvalidViewContractException::class);
        new RenderedOutput($bytes, new RenderLimits($maximum, 1));
    }
}
