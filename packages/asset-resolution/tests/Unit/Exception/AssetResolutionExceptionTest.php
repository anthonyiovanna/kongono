<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Unit\Exception;

use Kongono\AssetResolution\Exception\AssetNotFoundException;
use Kongono\AssetResolution\Exception\ExceptionInterface;
use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;
use Kongono\AssetResolution\Exception\StaleAssetResolutionArtifactException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AssetResolutionExceptionTest extends TestCase
{
    public function testFamilyIsNondisclosingAndRetainsPrevious(): void
    {
        $previous = new RuntimeException('private-path-and-url');
        $exception = InvalidAssetResolutionException::invalidArtifact('native_validation', previous: $previous);
        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertSame($previous, $exception->getPrevious());
        self::assertStringNotContainsString('private-path-and-url', (string) $exception);
        self::assertStringContainsString('KGN-ASSET-RESOLUTION-1931', (string) $exception);
        self::assertSame('KGN-ASSET-RESOLUTION-1933', AssetNotFoundException::missing()->diagnosticId());
        self::assertSame('KGN-ASSET-RESOLUTION-1932', StaleAssetResolutionArtifactException::stale()->diagnosticId());
    }
}
