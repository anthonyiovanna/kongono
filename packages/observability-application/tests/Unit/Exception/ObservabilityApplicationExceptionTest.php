<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Unit\Exception;

use Kongono\ObservabilityApplication\Exception\ApplicationObservabilityCleanupException;
use Kongono\ObservabilityApplication\Exception\ApplicationObservabilityLifecycleException;
use Kongono\ObservabilityApplication\Exception\ExceptionInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ObservabilityApplicationExceptionTest extends TestCase
{
    public function testLifecycleFactoriesAndCombinedFailureAreExactAndNondisclosing(): void
    {
        foreach ([
            ApplicationObservabilityLifecycleException::applicationClosed(),
            ApplicationObservabilityLifecycleException::operationCapacity(),
            ApplicationObservabilityLifecycleException::activeOperations(),
            ApplicationObservabilityLifecycleException::operationState(),
            ApplicationObservabilityLifecycleException::operationContext(),
        ] as $failure) {
            self::assertInstanceOf(ExceptionInterface::class, $failure);
            self::assertSame('KGN-OBSERVABILITY-APPLICATION-1001', $failure->diagnosticId());
            self::assertStringNotContainsString('secret', (string) $failure);
        }

        $primary = new RuntimeException('primary-secret');
        $first = new RuntimeException('first-secret');
        $second = new RuntimeException('second-secret');
        $combined = ApplicationObservabilityCleanupException::forFailures($primary, [$first, $second]);
        self::assertSame('KGN-OBSERVABILITY-APPLICATION-1002', $combined->diagnosticId());
        self::assertSame($primary, $combined->primaryFailure());
        self::assertSame([$first, $second], $combined->cleanupFailures());
        self::assertSame($primary, $combined->getPrevious());
        foreach (['primary-secret', 'first-secret', 'second-secret'] as $secret) {
            self::assertStringNotContainsString($secret, (string) $combined);
        }
    }
}
