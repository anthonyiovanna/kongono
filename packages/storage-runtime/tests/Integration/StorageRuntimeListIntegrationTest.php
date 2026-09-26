<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Integration;

use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageContract\Outcome\StorageListOutcome;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageRuntime\Runtime\ExactCurrentStorageRuntime;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageAdapter;
use PHPUnit\Framework\TestCase;

final class StorageRuntimeListIntegrationTest extends TestCase
{
    public function testCompletedEmptyPageRemainsCompleted(): void
    {
        $adapter = new ScriptedStorageAdapter(ScriptedStorageAdapter::makeDescriptor());
        $runtime = new ExactCurrentStorageRuntime(ScriptedStorageAdapter::binding($adapter));
        $result = $runtime->list(new StorageListRequest(ContractVersion::Version1, new StorageOperationIdentity('list-one'), new StorageNamespace('runtime-tests'), 3));
        self::assertSame(StorageListOutcome::Completed, $result->outcome());
        $page = $result->page();
        self::assertNotNull($page);
        self::assertSame([], $page->entries());
        self::assertTrue($page->complete());
    }
}
