<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Stream;

use Kongono\StorageContract\Stream\StorageByteSink;
use Kongono\StorageContract\Stream\StorageByteSource;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class StorageStreamContractTest extends TestCase
{
    public function testSourceAndSinkExposeOnlyExplicitBoundedLifecycleMethods(): void
    {
        self::assertSame(['read', 'exhausted', 'close', 'closed'], array_map(static fn(ReflectionMethod $method): string => $method->getName(), (new ReflectionClass(StorageByteSource::class))->getMethods()));
        self::assertSame(['write', 'close', 'abort', 'closed'], array_map(static fn(ReflectionMethod $method): string => $method->getName(), (new ReflectionClass(StorageByteSink::class))->getMethods()));
        self::assertTrue((new ReflectionClass(StorageByteSource::class))->isInterface());
        self::assertTrue((new ReflectionClass(StorageByteSink::class))->isInterface());
    }
}
