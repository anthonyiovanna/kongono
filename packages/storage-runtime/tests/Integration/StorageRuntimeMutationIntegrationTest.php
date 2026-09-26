<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Integration;

use Kongono\StorageContract\Condition\StorageMutationCondition;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Version\StorageObjectVersion;
use Kongono\StorageRuntime\Runtime\ExactCurrentStorageRuntime;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageAdapter;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageByteSource;
use PHPUnit\Framework\TestCase;

final class StorageRuntimeMutationIntegrationTest extends TestCase
{
    public function testCreateReplaceAndDeletePreserveConditionalSemantics(): void
    {
        $adapter = new ScriptedStorageAdapter(ScriptedStorageAdapter::makeDescriptor());
        $runtime = new ExactCurrentStorageRuntime(ScriptedStorageAdapter::binding($adapter));
        self::assertSame(StorageMutationOutcome::Completed, $runtime->create(ScriptedStorageAdapter::writeRequest('one'), new ScriptedStorageByteSource('one'))->outcome());
        self::assertSame(StorageMutationOutcome::Completed, $runtime->replace(ScriptedStorageAdapter::writeRequest('two', StorageOperation::Replace), new ScriptedStorageByteSource('two'))->outcome());
        $version = new StorageObjectVersion('version-zero');
        $request = new StorageDeleteRequest(ContractVersion::Version1, new StorageOperationIdentity('delete-one'), ScriptedStorageAdapter::identity(), StorageMutationCondition::ifVersion($version));
        self::assertSame(StorageMutationOutcome::Completed, $runtime->delete($request)->outcome());
        self::assertSame(3, $adapter->calls);
    }
}
