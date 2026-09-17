<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Tests\Unit;

use Kongono\CacheAdapterMemory\MemoryAdapterVersion;
use PHPUnit\Framework\TestCase;

final class MemoryAdapterVersionTest extends TestCase
{
    public function testVersionOneIsTheOnlyImplementationVersion(): void
    {
        self::assertSame([MemoryAdapterVersion::Version1], MemoryAdapterVersion::cases());
        self::assertSame(1, MemoryAdapterVersion::Version1->value);
    }
}
