<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Integration;

use Kongono\AssetAdapterContract\Artifact\AdapterBuildArtifactValidator;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use Kongono\AssetAdapterContract\Identity\ToolchainIdentity;
use Kongono\AssetAdapterContract\Output\AdapterOutputSet;
use Kongono\AssetAdapterContract\Tests\Fixture\AdapterContractProbe;
use PHPUnit\Framework\TestCase;

final class AdapterArtifactDeterminismTest extends TestCase
{
    public function testRepeatedEmptyBuildEvidenceIsByteIdentical(): void
    {
        $source = AdapterContractProbe::emptySourceSet();
        $profile = new AdapterProfileIdentity('vite/client.1');
        $toolchain = ToolchainIdentity::fromEvidence('toolchain');
        $validator = new AdapterBuildArtifactValidator();
        $left = $validator->create($source, $profile, $toolchain, 'kongono/asset-adapter-vite', new AdapterOutputSet($source, [], [], []));
        $right = $validator->create($source, $profile, $toolchain, 'kongono/asset-adapter-vite', new AdapterOutputSet($source, [], [], []));
        self::assertSame($left->bytes(), $right->bytes());
    }
}
