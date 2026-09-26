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
}
