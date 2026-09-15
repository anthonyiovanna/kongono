<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class AssetApplicationBoundaryTest extends TestCase
{
    public function testPackageHasClosedDirectDependenciesAndNoForbiddenCapabilities(): void
    {
        $root = dirname(__DIR__, 2);
        $composer = json_decode((string) file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($composer) || !isset($composer['require']) || !is_array($composer['require'])) {
            self::fail('Package manifest must contain a require object.');
        }
        self::assertSame([
            'php', 'kongono/api-metadata', 'kongono/diagnostics', 'kongono/asset-contract', 'kongono/asset-build', 'kongono/asset-adapter-contract', 'kongono/asset-resolution',
        ], array_keys($composer['require']));
        $source = '';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/src', \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (!$file instanceof \SplFileInfo) {
                self::fail('Source inventory must contain ordinary file information.');
            }
            if ($file->isFile()) {
                $source .= file_get_contents($file->getPathname());
            }
        }
        foreach (['AssetAdapterVite', 'ConsoleApplication', 'ViewApplication', 'proc_open', 'shell_exec', 'curl_'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
