<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Artifact;

use Kongono\AssetAdapterContract\Artifact\AdapterBuildArtifactValidator;
use Kongono\AssetAdapterContract\Exception\InvalidAdapterBuildArtifactException;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use Kongono\AssetAdapterContract\Identity\ToolchainIdentity;
use Kongono\AssetAdapterContract\Output\AdapterOutputSet;
use Kongono\AssetAdapterContract\Tests\Fixture\AdapterContractProbe;
use PHPUnit\Framework\TestCase;

final class AdapterBuildArtifactValidatorTest extends TestCase
{
    public function testValidatorRejectsStaleProfileAndDuplicateMembers(): void
    {
        $source = AdapterContractProbe::emptySourceSet();
        $profile = new AdapterProfileIdentity('vite/client.1');
        $toolchain = new ToolchainIdentity(str_repeat('a', 64));
        $validator = new AdapterBuildArtifactValidator();
        $artifact = $validator->create($source, $profile, $toolchain, 'kongono/asset-adapter-vite', new AdapterOutputSet($source, [], [], []));
        $this->expectException(InvalidAdapterBuildArtifactException::class);
        $validator->validate($source, new AdapterProfileIdentity('vite/client.2'), $toolchain, $artifact->bytes(), []);
    }
}
