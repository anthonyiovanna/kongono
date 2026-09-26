<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Unit\Exception;

use Kongono\StorageBuild\Exception\ExceptionInterface;
use Kongono\StorageBuild\Exception\InvalidStorageProfileException;
use Kongono\StorageBuild\Exception\StorageProfilePublicationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StorageBuildExceptionTest extends TestCase
{
    public function testExceptionPresentationIsDeterministicAndNondisclosing(): void
    {
        $failure = InvalidStorageProfileException::invalid('SECRET-profile/C:\\private\\token');
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame('KGN-STORAGE-BUILD-2201', $failure->diagnosticId());
        self::assertStringContainsString('violation=invalid_profile', (string) $failure);
        self::assertStringNotContainsString('SECRET-profile', (string) $failure);
    }

    public function testPublicationCleanupFailurePreservesPrimaryAndClosedTruth(): void
    {
        $primary = new RuntimeException('private-primary');
        $failure = StorageProfilePublicationException::failed('temporary_cleanup_failed', $primary, 'failed');
        $fields = $failure->diagnosticContext()->fields();
        self::assertSame($primary, $failure->getPrevious());
        self::assertCount(2, $fields);
        self::assertSame('violation', $fields[0]->key());
        self::assertSame('temporary_cleanup_failed', $fields[0]->value());
        self::assertSame('cleanup_status', $fields[1]->key());
        self::assertSame('failed', $fields[1]->value());
        self::assertStringNotContainsString('private-primary', (string) $failure);
    }
}
