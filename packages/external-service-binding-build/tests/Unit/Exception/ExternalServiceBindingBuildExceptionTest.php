<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Unit\Exception;

use Kongono\ExternalServiceBindingBuild\Exception\ExceptionInterface;
use Kongono\ExternalServiceBindingBuild\Exception\InvalidExternalServiceBindingArtifactException;
use Kongono\ExternalServiceBindingBuild\Exception\InvalidExternalServiceBindingSourceException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExternalServiceBindingBuildExceptionTest extends TestCase
{
    public function testHierarchyPresentationPreviousIdentityAndClosedTokensAreDeterministic(): void
    {
        $previous = new RuntimeException('foreign secret and trace');
        $failure = InvalidExternalServiceBindingArtifactException::invalid('not-allowed-raw', 'not-a-field', -2, -3, -4, -5, $previous);
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame($previous, $failure->getPrevious());
        self::assertSame('External-service binding artifact is invalid.', $failure->getMessage());
        self::assertStringContainsString('violation=unsupported_violation', (string) $failure);
        self::assertStringContainsString('field=unsupported_field', (string) $failure);
        self::assertStringNotContainsString('foreign secret and trace', (string) $failure);
        self::assertStringNotContainsString('#0', (string) $failure);
        self::assertSame((string) $failure, (string) $failure);
    }

    public function testSourceFactoryNeverProjectsRejectedRawValues(): void
    {
        $raw = 'REALISTIC-LOOKING-RAW-SECRET-VALUE';
        $failure = InvalidExternalServiceBindingSourceException::invalid($raw, $raw);
        self::assertStringNotContainsString($raw, $failure->getMessage());
        self::assertStringNotContainsString($raw, (string) $failure);
        foreach ($failure->diagnosticContext()->fields() as $field) {
            self::assertStringNotContainsString($raw, (string) $field->value());
        }
    }
}
