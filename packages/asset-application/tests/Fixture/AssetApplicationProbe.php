<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Tests\Fixture;

use Kongono\AssetApplication\Definition\AssetApplicationDefinition;

final class AssetApplicationProbe
{
    /** @return array{string,AssetApplicationDefinition,string,string} */
    public static function create(): array
    {
        $root = sys_get_temp_dir() . '/kongono-asset-application-' . bin2hex(random_bytes(8));
        $output = $root . '/var/assets';
        if (!mkdir($output, 0777, true)) {
            throw new \RuntimeException('Unable to create fixture.');
        }
        $css = "body { color: #123456; }\n";
        file_put_contents($root . '/app.css', $css);
        $source = $root . '/kongono.assets.json';
        file_put_contents($source, "{\"schema\":\"kongono.asset-application-source\",\"version\":1,\"contract_version\":1,\"declarations\":[{\"identity\":\"app.style\",\"kind\":\"style\",\"content_type\":\"text/css\",\"source_path\":\"app.css\"}],\"dependencies\":[],\"entry_points\":[\"app.style\"]}\n");

        return [$root, new AssetApplicationDefinition($root, $source, $output), $source, $css];
    }

    public static function remove(string $root): void
    {
        if (!is_dir($root)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                throw new \RuntimeException('Invalid fixture inventory.');
            }
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($root);
    }
}
