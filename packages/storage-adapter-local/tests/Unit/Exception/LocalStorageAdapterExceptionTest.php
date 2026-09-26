<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Unit\Exception;

use Kongono\StorageAdapterLocal\Exception\ExceptionInterface;
use Kongono\StorageAdapterLocal\Exception\LocalStorageOperationException;
use PHPUnit\Framework\TestCase;

final class LocalStorageAdapterExceptionTest extends TestCase
{
    public function testFamilyAndNativeMessageNondisclosure(): void
    {
        $e = LocalStorageOperationException::failed('operation_failed', 'read', new \RuntimeException('private-path'));
        self::assertInstanceOf(ExceptionInterface::class, $e);
        self::assertStringNotContainsString('private-path', (string) $e);
    }
}
