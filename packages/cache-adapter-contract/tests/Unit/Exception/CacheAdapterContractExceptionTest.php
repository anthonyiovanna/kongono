<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Unit\Exception;

use Kongono\CacheAdapterContract\Exception\CacheAdapterExecutionException;
use Kongono\CacheAdapterContract\Exception\ExceptionInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CacheAdapterContractExceptionTest extends TestCase
{
    public function testExecutionExceptionPreservesNativeCatchFamiliesSafely(): void
    {
        $previous = new RuntimeException('native-secret-marker');
        $exception = CacheAdapterExecutionException::impossibleState($previous);
        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertSame($previous, $exception->getPrevious());
        self::assertStringNotContainsString('native-secret-marker', (string) $exception);
    }
}
