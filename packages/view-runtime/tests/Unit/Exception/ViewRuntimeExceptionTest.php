<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Tests\Unit\Exception;

use Kongono\ViewRuntime\Exception\ArtifactLoadException;
use Kongono\ViewRuntime\Exception\InvalidRenderRequestException;
use Kongono\ViewRuntime\Exception\InvalidRuntimeArtifactException;
use Kongono\ViewRuntime\Exception\RenderBoundExceededException;
use Kongono\ViewRuntime\Exception\RenderRetirementException;
use Kongono\ViewRuntime\Exception\StaleRuntimeArtifactException;
use Kongono\ViewRuntime\Exception\ViewRenderingException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViewRuntimeExceptionTest extends TestCase
{
    public function testExceptionMappingAndStringificationAreClosedAndNonDisclosing(): void
    {
        $private = 'PRIVATE-C:\\secret\\artifact.php-<raw>';
        $exceptions = [
            InvalidRuntimeArtifactException::invalid($private, previous: new RuntimeException($private)),
            StaleRuntimeArtifactException::mismatch($private),
            ArtifactLoadException::failed($private, previous: new RuntimeException($private)),
            InvalidRenderRequestException::invalid($private, $private, new RuntimeException($private)),
            ViewRenderingException::unsupportedContext($private, 2),
            RenderBoundExceededException::exceeded($private, 2, 3, 1),
            ViewRenderingException::failed($private, previous: new RuntimeException($private)),
            RenderRetirementException::failed($private, new RuntimeException($private)),
        ];
        self::assertSame(array_map(static fn(int $number): string => 'KGN-VIEW-RUNTIME-' . $number, range(1830, 1837)), array_map(static fn($failure): string => $failure->diagnosticId(), $exceptions));
        foreach ($exceptions as $failure) {
            self::assertStringNotContainsString($private, $failure->getMessage());
            self::assertStringNotContainsString($private, (string) $failure);
        }
    }
}
