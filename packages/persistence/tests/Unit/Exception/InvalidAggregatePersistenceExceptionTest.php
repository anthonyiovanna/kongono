<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit\Exception;

use Kongono\Persistence\Exception\ExceptionInterface;
use Kongono\Persistence\Exception\InvalidAggregatePersistenceException;
use PHPUnit\Framework\TestCase;

final class InvalidAggregatePersistenceExceptionTest extends TestCase
{
    public function testDiagnosticIsFixedSafeAndPackageLocal(): void
    {
        $failure = InvalidAggregatePersistenceException::invalid();
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame('KGN-PERSISTENCE-1009', $failure->diagnosticId());
        self::assertSame(
            ['persistence', 'aggregate_repository_evidence', 'regenerate_aggregate_evidence'],
            array_map(static fn($field): string => (string) $field->value(), $failure->diagnosticContext()->fields()),
        );
        self::assertStringNotContainsString('identity', (string) $failure);
    }
}
