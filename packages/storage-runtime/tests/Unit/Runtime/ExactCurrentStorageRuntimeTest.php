<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Unit\Runtime;

use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageRuntime\Runtime\ExactCurrentStorageRuntime;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageAdapter;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageByteSource;
use PHPUnit\Framework\TestCase;

final class ExactCurrentStorageRuntimeTest extends TestCase
{
    public function testCreateConsumesAndClosesSource(): void
    {
        $adapter = new ScriptedStorageAdapter(ScriptedStorageAdapter::makeDescriptor());
        $runtime = new ExactCurrentStorageRuntime(ScriptedStorageAdapter::binding($adapter));
        $source = new ScriptedStorageByteSource('data');
        $result = $runtime->create(ScriptedStorageAdapter::writeRequest('data'), $source);
        self::assertSame(StorageMutationOutcome::Completed, $result->outcome());
        self::assertTrue($source->closed());
        self::assertSame(1, $adapter->calls);
    }
}
