<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Unit\Exception;

use Kongono\CacheContract\Exception\ExceptionInterface;
use Kongono\CacheContract\Exception\InvalidCacheContractException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class CacheContractExceptionTest extends TestCase
{
    public function testHierarchyFactoriesAndFallbacksAreDeterministicAndNondisclosing(): void
    {
        $previous = new RuntimeException('private-causal-secret');
        $failures = [
            InvalidCacheContractException::invalidIdentity('private-identity-secret', $previous),
            InvalidCacheContractException::invalidValueBytes('private-value-secret', 4, 3),
            InvalidCacheContractException::invalidFreshnessEvidence('private-time-secret'),
            InvalidCacheContractException::invalidEntryVersion('private-version-secret'),
            InvalidCacheContractException::invalidEntryEvidence('private-entry-secret', 'private-operation', 'private-outcome'),
            InvalidCacheContractException::contractBoundExceeded('private-bound-secret', 65, 64),
        ];
        self::assertSame(array_map(static fn(int $number): string => 'KGN-CACHE-CONTRACT-' . $number, range(2001, 2006)), array_map(static fn(InvalidCacheContractException $failure): string => $failure->diagnosticId(), $failures));
        self::assertInstanceOf(ExceptionInterface::class, $failures[0]);
        self::assertInstanceOf(Throwable::class, $failures[0]);
        self::assertInstanceOf(RuntimeException::class, $failures[0]);
        self::assertSame($previous, $failures[0]->getPrevious());

        foreach ($failures as $failure) {
            foreach (['private-', 'private-causal-secret', 'C:\\private\\cache', 'https://private.example/cache'] as $private) {
                self::assertStringNotContainsString($private, $failure->getMessage());
                self::assertStringNotContainsString($private, (string) $failure);
                foreach ($failure->diagnosticContext()->fields() as $field) {
                    self::assertStringNotContainsString($private, (string) $field->value());
                }
            }
        }
        self::assertSame('unsupported_operation', $failures[4]->diagnosticContext()->fields()[1]->value());
        self::assertSame((string) InvalidCacheContractException::invalidIdentity('cache_namespace_grammar'), (string) InvalidCacheContractException::invalidIdentity('cache_namespace_grammar'));
    }
}
