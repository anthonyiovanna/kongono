<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

#[CoversNothing]final class ViteAdapterBoundaryTest extends TestCase
{
    public function testExactProductionInventoryAndApiStatuses(): void
    {
        $root = dirname(__DIR__, 2) . '/src';
        $files = [];
        $counts = ['Experimental' => 0, 'Public' => 0, 'Internal' => 0];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files, SORT_STRING);
        self::assertCount(22, $files);
        foreach ($files as $file) {
            $relative = str_replace('\\', '/', substr($file, strlen($root) + 1));
            $class = 'Kongono\\AssetAdapterVite\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            self::assertTrue(class_exists($class) || interface_exists($class) || enum_exists($class), $class);
            $attributes = (new \ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            ++$counts[$attributes[0]->newInstance()->status->name];
        }
        self::assertSame(['Experimental' => 12, 'Public' => 1, 'Internal' => 9], $counts);
    }
}
