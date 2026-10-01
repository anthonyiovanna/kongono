<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Unit\Internal;

use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageRuntime\Internal\RuntimeGuard;
use Kongono\StorageRuntime\Internal\ValidatedReadSource;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageByteSource;
use PHPUnit\Framework\TestCase;

final class ValidatedReadSourceTest extends TestCase
{
    public function testExhaustionValidatesAndRetires(): void
    {
        $guard = new RuntimeGuard();
        $scope = $guard->enter(new StorageOperationIdentity('read-source'), StorageOperation::Read);
        $source = new ValidatedReadSource(new ScriptedStorageByteSource('abc'), $scope, 3, 3, new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', 'abc')));
        self::assertSame('abc', $source->read(3)?->bytes());
        self::assertNull($source->read(3));
        self::assertTrue($source->closed());
        self::assertTrue($scope->retired());
    }

    public function testTerminalCloseFailureIsNotCaughtAndRecomposed(): void
    {
        foreach ([false, true] as $failRead) {
            $guard = new RuntimeGuard();
            $scope = $guard->enter(new StorageOperationIdentity('read-cleanup'), StorageOperation::Read);
            $underlying = new ScriptedStorageByteSource('', failRead: $failRead, failClose: true);
            $source = new ValidatedReadSource($underlying, $scope, 0, 4, null);
            try {
                $source->read(4);
                self::fail('Cleanup failure lost.');
            } catch (\Kongono\StorageRuntime\Exception\StorageRuntimeCleanupException $failure) {
                self::assertFalse($failRead);
                self::assertCount(1, $failure->cleanupFailures());
            } catch (\Kongono\StorageRuntime\Exception\StorageRuntimeOperationAndCleanupException $failure) {
                self::assertTrue($failRead);
                self::assertInstanceOf(\Kongono\StorageRuntime\Exception\StorageRuntimeOperationException::class, $failure->primaryFailure());
                self::assertSame('fixture-read-failure', $failure->primaryFailure()->getPrevious()?->getMessage());
                self::assertCount(1, $failure->cleanupFailures());
            }
            self::assertSame(1, $underlying->closeCalls);
            self::assertTrue($scope->retired());
            self::assertTrue($source->closed());
            $source->close();
            self::assertSame(1, $underlying->closeCalls);
            $next = $guard->enter(new StorageOperationIdentity('next-read'), StorageOperation::Read);
            $next->retire();
        }
    }

    public function testComposerPreservesExactPrimaryAndOrderedCleanupCauses(): void
    {
        $primary = new \Error('synthetic-primary');
        $close = new \RuntimeException('synthetic-close');
        $scope = new \RuntimeException('synthetic-scope');
        try {
            \Kongono\StorageRuntime\Internal\LifecycleFailureComposer::finish('read', $primary, [$close, $scope]);
            self::fail('Combined failure lost.');
        } catch (\Kongono\StorageRuntime\Exception\StorageRuntimeOperationAndCleanupException $failure) {
            self::assertSame($primary, $failure->primaryFailure());
            self::assertSame([$close, $scope], $failure->cleanupFailures());
        }
    }
}
