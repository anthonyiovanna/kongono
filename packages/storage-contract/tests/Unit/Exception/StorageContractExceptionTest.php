<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Exception;

use Kongono\StorageContract\Exception\ExceptionInterface;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class StorageContractExceptionTest extends TestCase
{
    public function testHierarchyFactoriesAndFallbacksAreDeterministicAndNondisclosing(): void
    {
        $previous = new RuntimeException('private-causal-secret');
        $failures = [
            InvalidStorageContractException::invalidIdentity('private-identity-secret', $previous),
            InvalidStorageContractException::invalidCapability('private-capability-secret', 'private-capability'),
            InvalidStorageContractException::invalidContentEvidence('private-content-secret', 'private-digest'),
            InvalidStorageContractException::invalidMutationCondition('private-condition-secret', 'private-operation'),
            InvalidStorageContractException::invalidRequest('private-request-secret', 'private-operation'),
            InvalidStorageContractException::invalidResult('private-result-secret', 'private-outcome', 2, 1),
            InvalidStorageContractException::invalidStreamContract('private-stream-secret'),
            InvalidStorageContractException::contractBoundExceeded('private-bound-secret', 64, 65),
        ];
        self::assertSame(array_map(static fn(int $number): string => 'KGN-STORAGE-CONTRACT-' . $number, range(2101, 2108)), array_map(static fn(InvalidStorageContractException $failure): string => $failure->diagnosticId(), $failures));
        self::assertInstanceOf(ExceptionInterface::class, $failures[0]);
        self::assertInstanceOf(Throwable::class, $failures[0]);
        self::assertInstanceOf(RuntimeException::class, $failures[0]);
        self::assertSame($previous, $failures[0]->getPrevious());

        foreach ($failures as $failure) {
            foreach (['private-', 'private-causal-secret'] as $private) {
                self::assertStringNotContainsString($private, $failure->getMessage());
                self::assertStringNotContainsString($private, (string) $failure);
                foreach ($failure->diagnosticContext()->fields() as $field) {
                    self::assertStringNotContainsString($private, (string) $field->value());
                }
            }
        }
        self::assertSame((string) InvalidStorageContractException::invalidIdentity('storage_namespace_grammar'), (string) InvalidStorageContractException::invalidIdentity('storage_namespace_grammar'));
    }
}
