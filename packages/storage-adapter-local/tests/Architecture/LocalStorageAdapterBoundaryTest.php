<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class LocalStorageAdapterBoundaryTest extends TestCase
{
    public function testProductionInventoryIsExact(): void
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src', \FilesystemIterator::SKIP_DOTS));
        $files = [];
        foreach ($it as $f) {
            if (!$f instanceof \SplFileInfo) {
                self::fail('Production inventory iterator returned an unexpected value.');
            }
            if ($f->getExtension() === 'php') {
                $files[] = $f->getPathname();
            }
        }self::assertCount(18, $files);
    }
}
