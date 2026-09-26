<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Unit\Exception;

use Kongono\StorageRuntime\Exception\InvalidStorageRuntimeException;
use Kongono\StorageRuntime\Exception\StorageRuntimeCleanupException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StorageRuntimeExceptionTest extends TestCase
{
    public function testPresentationIsNondisclosingAndCleanupIsRetained(): void
    {
        $secret = 'native-secret-marker';
        $prior = new RuntimeException($secret);
        $failure = InvalidStorageRuntimeException::binding('artifact_invalid', $prior);
        self::assertStringNotContainsString($secret, (string) $failure);
        $cleanup = new StorageRuntimeCleanupException('read', [$prior]);
        self::assertSame([$prior], $cleanup->cleanupFailures());
        self::assertStringNotContainsString($secret, (string) $cleanup);
    }
}
