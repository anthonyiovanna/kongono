<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Unit\Exception;

use Kongono\ViewBuild\Exception\ExceptionInterface;
use Kongono\ViewBuild\Exception\InvalidViewSourceException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViewBuildExceptionTest extends TestCase
{
    public function testExceptionPreservesPreviousButNeverPrintsItsMessage(): void
    {
        $secret = 'private-source-value';
        $previous = new RuntimeException($secret);
        $exception = InvalidViewSourceException::invalid('source_json', $previous);
        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertSame($previous, $exception->getPrevious());
        self::assertStringContainsString('KGN-VIEW-BUILD-1810', (string) $exception);
        self::assertStringNotContainsString($secret, (string) $exception);
    }
}
