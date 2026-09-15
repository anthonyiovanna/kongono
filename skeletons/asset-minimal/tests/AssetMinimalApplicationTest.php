<?php

declare(strict_types=1);

namespace AssetMinimalApp\Tests;

use AssetMinimalApp\AssetWorkflow;
use AssetMinimalApp\ViewAssetContext;
use Kongono\ViewContract\Schema\ViewContextSchema;
use Kongono\ViewContract\Value\ViewContext;
use PHPUnit\Framework\TestCase;

final class AssetMinimalApplicationTest extends TestCase
{
    public function testOwnershipInventoryAndExplicitViewMapping(): void
    {
        $root = dirname(__DIR__);
        $manifest = json_decode((string) file_get_contents($root . '/kongono.skeleton.json'), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($manifest) || !isset($manifest['template_paths']) || !is_array($manifest['template_paths'])) {
            self::fail('Ownership metadata must contain template paths.');
        }
        $paths = $manifest['template_paths'];
        foreach ($paths as $path) {
            if (!is_string($path)) {
                self::fail('Template paths must be strings.');
            }
        }
        $sorted = $paths;
        sort($sorted, SORT_STRING);
        self::assertSame($sorted, $paths);
        foreach ($paths as $path) {
            self::assertFileExists($root . '/' . $path);
        }

        $copy = sys_get_temp_dir() . '/kongono-asset-minimal-test-' . bin2hex(random_bytes(8));
        mkdir($copy . '/assets', 0777, true);
        mkdir($copy . '/var/assets', 0777, true);
        copy($root . '/assets/app.css', $copy . '/assets/app.css');
        copy($root . '/kongono.assets.json', $copy . '/kongono.assets.json');
        try {
            $workflow = new AssetWorkflow(str_replace('\\', '/', $copy));
            $workflow->build();
            [$schema, $context] = ViewAssetContext::fromResolved($workflow->resolve());
            self::assertInstanceOf(ViewContextSchema::class, $schema);
            self::assertInstanceOf(ViewContext::class, $context);
        } finally {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($copy, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $entry) {
                if (!$entry instanceof \SplFileInfo) {
                    self::fail('Copied inventory must contain file information.');
                }
                $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
            }
            rmdir($copy);
        }
    }
}
