<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Tests\Unit\Exception;

use Kongono\ApplicationGraphComposition\Exception\ExceptionInterface;
use Kongono\ApplicationGraphComposition\Exception\InvalidCompositionGraphProjectionException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CompositionGraphExceptionTest extends TestCase
{
    public function testFailureIsNativeDeterministicAndRetainsPreviousThrowable(): void
    {
        $previous = new RuntimeException('private');
        $exception = InvalidCompositionGraphProjectionException::invalidArtifact('artifact_json', $previous);
        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertSame($previous, $exception->getPrevious());
        self::assertStringContainsString('KGN-GRAPH-1111', (string) $exception);
        self::assertStringNotContainsString('private', (string) $exception);
    }
}
