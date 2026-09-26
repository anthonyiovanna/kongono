<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Unit\Configuration;

use Kongono\StorageAdapterLocal\Configuration\LocalStorageRoot;
use Kongono\StorageAdapterLocal\Exception\InvalidLocalStorageConfigurationException;
use PHPUnit\Framework\TestCase;

final class LocalStorageRootTest extends TestCase
{
    public function testExplicitExistingDirectoryIsAccepted(): void
    {
        $p = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-root-' . bin2hex(random_bytes(6));
        mkdir($p);
        try {
            self::assertNotSame('', (new LocalStorageRoot($p))->resolvedPath());
        } finally {
            rmdir($p);
        }
    }
    public function testRelativeRootIsRejected(): void
    {
        $this->expectException(InvalidLocalStorageConfigurationException::class);
        new LocalStorageRoot('relative/path');
    }
}
