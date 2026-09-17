<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Unit\Exception;

use Kongono\CacheBuild\Exception\CacheProfilePublicationException;
use Kongono\CacheBuild\Exception\ExceptionInterface;
use Kongono\CacheBuild\Exception\InvalidCacheProfileException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CacheBuildExceptionTest extends TestCase
{
    public function testExceptionPresentationIsDeterministic(): void
    {
        $failure = InvalidCacheProfileException::invalid('unsupported caller secret');
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame('KGN-CACHE-BUILD-2011', $failure->diagnosticId());
        self::assertStringContainsString('violation=invalid_profile', (string) $failure);
        self::assertStringNotContainsString('unsupported caller secret', (string) $failure);
    }

    public function testPublicationCleanupFailurePreservesPrimaryAndClosedTruth(): void
    {
        $primary = new RuntimeException('private-primary');
        $failure = CacheProfilePublicationException::failed('temporary_cleanup_failed', $primary, 'failed');
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
