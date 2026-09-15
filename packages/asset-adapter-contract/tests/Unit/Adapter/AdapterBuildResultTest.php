<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Adapter;

use Kongono\AssetAdapterContract\Adapter\AdapterBuildResult;
use Kongono\AssetAdapterContract\Artifact\AdapterBuildArtifactValidator;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use Kongono\AssetAdapterContract\Identity\ToolchainIdentity;
use Kongono\AssetAdapterContract\Output\AdapterOutputSet;
use Kongono\AssetAdapterContract\Tests\Fixture\AdapterContractProbe;
use PHPUnit\Framework\TestCase;

final class AdapterBuildResultTest extends TestCase
{
    public function testResultRevalidatesCompleteRelation(): void
    {
        $source = AdapterContractProbe::emptySourceSet();
        $set = new AdapterOutputSet($source, [], [], []);
        $artifact = (new AdapterBuildArtifactValidator())->create($source, new AdapterProfileIdentity('vite/client.1'), new ToolchainIdentity(str_repeat('a', 64)), 'kongono/asset-adapter-vite', $set);
        $result = new AdapterBuildResult($artifact, $set, []);
        self::assertSame([], $result->outputBytesByPath());
    }
}
