<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Tests\Unit\Exception;

use Kongono\StorageAdapterContract\Exception\ExceptionInterface;
use Kongono\StorageAdapterContract\Exception\StorageAdapterExecutionException;
use PHPUnit\Framework\TestCase;

final class StorageAdapterContractExceptionTest extends TestCase
{
    public function testFamilyAndSafeString(): void
    {
        $e = StorageAdapterExecutionException::impossibleState(new \RuntimeException('secret'));
        self::assertInstanceOf(ExceptionInterface::class, $e);
        self::assertStringNotContainsString('secret', (string) $e);
    }
}
