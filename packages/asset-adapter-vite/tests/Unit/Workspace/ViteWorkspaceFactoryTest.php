<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Unit\Workspace;

use Kongono\AssetAdapterContract\Tests\Fixture\AdapterContractProbe;
use Kongono\AssetAdapterVite\Workspace\ViteWorkspaceFactory;
use PHPUnit\Framework\TestCase;

final class ViteWorkspaceFactoryTest extends TestCase
{
    public function testWorkspaceContainsOnlySourceOutputAndFixedConfigThenCleans(): void
    {
        $parent = str_replace('\\', '/', sys_get_temp_dir());
        $workspace = (new ViteWorkspaceFactory($parent))->create(AdapterContractProbe::emptySourceSet());
        $root = $workspace->root();
        self::assertSame(['.','..','output','source','vite.config.mjs'], scandir($root));
        $config = file_get_contents($workspace->configPath());
        self::assertIsString($config);
        self::assertStringContainsString("target: 'es2022'", $config);
        $workspace->cleanup();
        self::assertDirectoryDoesNotExist($root);
    }
}
