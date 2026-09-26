<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Unit;

use Kongono\StorageRuntime\StorageRuntimeVersion;
use PHPUnit\Framework\TestCase;

final class StorageRuntimeVersionTest extends TestCase
{
    public function testVersionOneIsClosed(): void
    {
        self::assertSame([StorageRuntimeVersion::Version1], StorageRuntimeVersion::cases());
        self::assertSame(1, StorageRuntimeVersion::Version1->value);
    }
}
