<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Exception;

use Kongono\ViewContract\Exception\ExceptionInterface;
use Kongono\ViewContract\Exception\InvalidViewContractException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class ViewContractExceptionTest extends TestCase
{
    public function testHierarchyNativeCompatibilityPreviousAndSafeFallback(): void
    {
        $previous = new RuntimeException('private-causal-secret');
        $failure = InvalidViewContractException::invalidIdentity('private-unsupported-token', $previous);
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertInstanceOf(Throwable::class, $failure);
        self::assertInstanceOf(RuntimeException::class, $failure);
        self::assertSame($previous, $failure->getPrevious());
        self::assertSame('KGN-VIEW-CONTRACT-1801', $failure->diagnosticId());
        self::assertSame('unsupported_violation', $failure->diagnosticContext()->fields()[0]->value());

        foreach (['private-unsupported-token', 'private-causal-secret', 'C:\\private\\template.php', '<script>secret</script>'] as $private) {
            self::assertStringNotContainsString($private, $failure->getMessage());
            self::assertStringNotContainsString($private, (string) $failure);
            foreach ($failure->diagnosticContext()->fields() as $field) {
                self::assertStringNotContainsString($private, (string) $field->value());
            }
        }
    }

    public function testRepeatedInvalidOperationsAreDeterministicAndIsolated(): void
    {
        $first = InvalidViewContractException::invalidPresentationValue('not-accepted');
        $second = InvalidViewContractException::invalidPresentationValue('not-accepted');
        self::assertSame($first->getMessage(), $second->getMessage());
        self::assertSame((string) $first, (string) $second);
        self::assertNotSame($first, $second);
    }
}
