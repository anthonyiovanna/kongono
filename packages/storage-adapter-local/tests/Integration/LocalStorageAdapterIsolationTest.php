<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Integration;

use Kongono\StorageAdapterContract\Tests\Fixture\StorageFixture;
use Kongono\StorageAdapterLocal\Adapter\ContainedLocalStorageAdapter;
use Kongono\StorageAdapterLocal\Configuration\LocalStorageRoot;
use PHPUnit\Framework\TestCase;

final class LocalStorageAdapterIsolationTest extends TestCase
{
    public function testIndependentRootsProduceIndependentAdapters(): void
    {
        $a = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-a-' . bin2hex(random_bytes(4));
        $b = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-b-' . bin2hex(random_bytes(4));
        mkdir($a);
        mkdir($b);
        try {
            $x = new ContainedLocalStorageAdapter(new LocalStorageRoot($a), StorageFixture::artifact(), new \Kongono\StorageBuild\Profile\StorageProfileName('local-test'));
            $y = new ContainedLocalStorageAdapter(new LocalStorageRoot($b), StorageFixture::artifact(), new \Kongono\StorageBuild\Profile\StorageProfileName('local-test'));
            self::assertNotSame($x, $y);
        } finally {
            rmdir($a);
            rmdir($b);
        }
    }
}
