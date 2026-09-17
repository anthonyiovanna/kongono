<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Tests\Unit\Exception;

use Kongono\CacheAdapterMemory\Configuration\MemoryAdapterCapacity;
use Kongono\CacheAdapterMemory\Exception\ExceptionInterface;
use Kongono\CacheAdapterMemory\Exception\InvalidMemoryCacheAdapterException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MemoryCacheAdapterExceptionTest extends TestCase
{
    public function testInvalidCapacityUsesNativeAndPackageCatchFamilies(): void
    {
        try {
            new MemoryAdapterCapacity(0, 1, 1);
            self::fail('Expected invalid capacity.');
        } catch (InvalidMemoryCacheAdapterException $exception) {
            self::assertInstanceOf(RuntimeException::class, $exception);
            self::assertInstanceOf(ExceptionInterface::class, $exception);
            self::assertSame('KGN-CACHE-ADAPTER-MEMORY-2021', $exception->diagnosticId());
        }
    }
}
