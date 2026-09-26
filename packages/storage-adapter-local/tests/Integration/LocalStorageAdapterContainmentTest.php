<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Integration;

use Kongono\StorageAdapterLocal\Configuration\LocalStorageRoot;
use Kongono\StorageAdapterLocal\Exception\InvalidLocalStorageConfigurationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LocalStorageAdapterContainmentTest extends TestCase
{
    #[DataProvider('invalidRoots')] public function testUnsafeRootFormsAreRejected(string $root): void
    {
        $this->expectException(InvalidLocalStorageConfigurationException::class);
        new LocalStorageRoot($root);
    }

    /** @return list<array{string}> */
    public static function invalidRoots(): array
    {
        return [['../escape'],['php://memory'],['C:/tmp/*'],['C:/tmp:stream'],['C:/CON']];
    }
}
