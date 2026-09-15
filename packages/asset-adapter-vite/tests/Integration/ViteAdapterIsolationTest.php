<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Integration;

use Kongono\AssetAdapterContract\Tests\Fixture\AdapterContractProbe;
use Kongono\AssetAdapterVite\Workspace\ViteWorkspace;
use Kongono\AssetAdapterVite\Workspace\ViteWorkspaceFactory;
use PHPUnit\Framework\TestCase;

final class ViteAdapterIsolationTest extends TestCase
{
    public function testSequentialAndFiberWorkspacesAreDistinctAndOwned(): void
    {
        $factory = new ViteWorkspaceFactory(str_replace('\\', '/', sys_get_temp_dir()));
        $left = $factory->create(AdapterContractProbe::emptySourceSet());
        $fiber = new \Fiber(static fn() => $factory->create(AdapterContractProbe::emptySourceSet()));
        $fiber->start();
        $right = $fiber->getReturn();
        self::assertInstanceOf(ViteWorkspace::class, $right);
        self::assertNotSame($left->root(), $right->root());
        $leftRoot = $left->root();
        $rightRoot = $right->root();
        $left->cleanup();
        $right->cleanup();
        self::assertDirectoryDoesNotExist($leftRoot);
        self::assertDirectoryDoesNotExist($rightRoot);
    }
}
