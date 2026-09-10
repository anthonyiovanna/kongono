<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Tests\Unit\Exception;

use Kongono\ExternalServiceResolverSystem\Exception\ExceptionInterface;
use Kongono\ExternalServiceResolverSystem\Exception\InvalidSystemResolverProfileException;
use Kongono\ExternalServiceResolverSystem\Exception\SystemResolverEnvironmentException;
use PHPUnit\Framework\TestCase;

final class SystemResolverExceptionTest extends TestCase
{
    public function testExceptionsExposeDeterministicNondisclosingEvidence(): void
    {
        $profile = InvalidSystemResolverProfileException::candidateLimit(-99);
        self::assertInstanceOf(ExceptionInterface::class, $profile);
        self::assertSame('KGN-RESOLVER-1618', $profile->diagnosticId());
        self::assertSame($profile::class . ': [KGN-RESOLVER-1618] System resolver profile is invalid.', (string) $profile);
        self::assertStringNotContainsString('-99', (string) $profile);

        $environment = SystemResolverEnvironmentException::unavailable();
        self::assertSame('KGN-RESOLVER-1619', $environment->diagnosticId());
        self::assertNull($environment->getPrevious());
    }
}
