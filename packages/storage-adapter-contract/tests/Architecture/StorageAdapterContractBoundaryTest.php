<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class StorageAdapterContractBoundaryTest extends TestCase
{
    public function testProductionInventoryIsExact(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src', \FilesystemIterator::SKIP_DOTS));
        $php = [];
        foreach ($files as $f) {
            if (!$f instanceof \SplFileInfo) {
                self::fail('Production inventory iterator returned an unexpected value.');
            }
            if ($f->getExtension() === 'php') {
                $php[] = $f->getPathname();
            }
        }self::assertCount(15, $php);
    }
}
