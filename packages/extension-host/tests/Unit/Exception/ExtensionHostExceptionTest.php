<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Tests\Unit\Exception;

use Kongono\ExtensionHost\Exception\ExceptionInterface;
use Kongono\ExtensionHost\Exception\ExtensionHostLifecycleException;
use Kongono\ExtensionHost\Exception\InvalidGeneratedExtensionHostException;
use Kongono\ExtensionHost\Internal\LifecycleFailureComposer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExtensionHostExceptionTest extends TestCase
{
    public function testPackageFailuresAreFixedAndDoNotStringifyPreviousMessages(): void
    {
        $cause = new RuntimeException('private C:\\secret password=hunter2');
        $failure = InvalidGeneratedExtensionHostException::invalid('require_failed', $cause);
        self::assertInstanceOf(ExceptionInterface::class, $failure);
        self::assertSame($cause, $failure->getPrevious());
        self::assertStringNotContainsString($cause->getMessage(), $failure->getMessage());
        self::assertStringNotContainsString($cause->getMessage(), (string) $failure);
    }

    public function testPrimaryAndCleanupMatrixPreservesExactIdentityAndOrder(): void
    {
        $primary = new RuntimeException('primary');
        $cleanupOne = new RuntimeException('cleanup-one');
        $cleanupTwo = new RuntimeException('cleanup-two');

        LifecycleFailureComposer::throwIfNeeded(null, []);
        $this->expectExact($primary, fn() => LifecycleFailureComposer::throwIfNeeded($primary, []));
        $this->expectExact($cleanupOne, fn() => LifecycleFailureComposer::throwIfNeeded(null, [$cleanupOne]));

        try {
            LifecycleFailureComposer::throwIfNeeded($primary, [$cleanupOne, $cleanupTwo]);
            self::fail('Combined failure was not thrown.');
        } catch (ExtensionHostLifecycleException $failure) {
            self::assertSame($primary, $failure->primaryFailure());
            self::assertSame([$cleanupOne, $cleanupTwo], $failure->cleanupFailures());
            self::assertSame($primary, $failure->getPrevious());
            self::assertStringNotContainsString('cleanup-one', (string) $failure);
        }

        try {
            LifecycleFailureComposer::throwIfNeeded(null, [$cleanupOne, $cleanupTwo]);
            self::fail('Multiple cleanup failures were not combined.');
        } catch (ExtensionHostLifecycleException $failure) {
            self::assertNull($failure->primaryFailure());
            self::assertSame([$cleanupOne, $cleanupTwo], $failure->cleanupFailures());
            self::assertSame($cleanupOne, $failure->getPrevious());
        }
    }

    /** @param callable(): void $operation */
    private function expectExact(RuntimeException $expected, callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected exact failure was not thrown.');
        } catch (RuntimeException $failure) {
            self::assertSame($expected, $failure);
        }
    }
}
