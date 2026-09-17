<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Unit;

use Kongono\CacheRuntime\CacheRuntimeVersion;
use PHPUnit\Framework\TestCase;

final class CacheRuntimeVersionTest extends TestCase
{
    public function testVersionOneIsTheOnlyVersion(): void
    {
        self::assertSame([CacheRuntimeVersion::Version1], CacheRuntimeVersion::cases());
        self::assertSame(1, CacheRuntimeVersion::Version1->value);
    }
}
