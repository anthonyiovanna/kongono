<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Unit\Exception;

use Kongono\CacheRuntime\Exception\CacheRuntimeOperationException;
use Kongono\CacheRuntime\Exception\ExceptionInterface;
use Kongono\CacheRuntime\Exception\InvalidCacheRuntimeException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CacheRuntimeExceptionTest extends TestCase
{
    public function testHierarchyCausesFallbacksAndStringificationAreNondisclosing(): void
    {
        $previous = new RuntimeException('private-provider-message');
        $failures = [
            InvalidCacheRuntimeException::invalidBinding('private-binding', $previous),
            InvalidCacheRuntimeException::invalidOperation('private-operation', 'private-operation'),
            InvalidCacheRuntimeException::invalidAdapterEvidence('private-evidence', 'private-operation', 'private-outcome'),
            CacheRuntimeOperationException::adapterFailed('lookup', $previous),
            CacheRuntimeOperationException::reentrant('store'),
        ];
        self::assertSame(array_map(static fn(int $number): string => 'KGN-CACHE-RUNTIME-' . $number, range(2031, 2035)), array_map(static fn(ExceptionInterface $failure): string => $failure->diagnosticId(), $failures));
        self::assertSame($previous, $failures[0]->getPrevious());
        self::assertSame($previous, $failures[3]->getPrevious());
        foreach ($failures as $failure) {
            foreach (['private-', 'private-provider-message', 'secret-key', 'C:\\private\\cache'] as $private) {
                self::assertStringNotContainsString($private, $failure->getMessage());
                self::assertStringNotContainsString($private, (string) $failure);
                foreach ($failure->diagnosticContext()->fields() as $field) {
                    self::assertStringNotContainsString($private, (string) $field->value());
                }
            }
        }
    }
}
