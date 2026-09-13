<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Unit\Source;

use Kongono\ViewBuild\Exception\InvalidViewSourceException;
use Kongono\ViewBuild\Source\SourcePointer;
use PHPUnit\Framework\TestCase;

final class SourcePointerTest extends TestCase
{
    public function testPointerIsCanonicalBoundedEvidence(): void
    {
        $pointer = new SourcePointer('/views/0/body/2');
        self::assertSame('/views/0/body/2', $pointer->value());
        self::assertTrue($pointer->equals(new SourcePointer('/views/0/body/2')));
    }

    public function testPointerRejectsNonPointerShape(): void
    {
        $this->expectException(InvalidViewSourceException::class);
        new SourcePointer('views/0/body/0');
    }
}
