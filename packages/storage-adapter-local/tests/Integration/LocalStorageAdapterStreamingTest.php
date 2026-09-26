<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Integration;

use Kongono\StorageContract\Stream\StorageByteSource;
use PHPUnit\Framework\TestCase;

final class LocalStorageAdapterStreamingTest extends TestCase
{
    public function testSourceContractRequiresExplicitLifecycle(): void
    {
        $methods = array_map(static fn(\ReflectionMethod $m): string => $m->getName(), (new \ReflectionClass(StorageByteSource::class))->getMethods());
        self::assertSame(['read','exhausted','close','closed'], $methods);
    }
}
