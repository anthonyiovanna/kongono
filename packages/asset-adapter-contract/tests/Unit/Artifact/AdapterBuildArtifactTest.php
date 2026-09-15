<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Artifact;

use Kongono\AssetAdapterContract\Artifact\AdapterBuildArtifactValidator;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use Kongono\AssetAdapterContract\Identity\ToolchainIdentity;
use Kongono\AssetAdapterContract\Output\AdapterOutputSet;
use Kongono\AssetAdapterContract\Tests\Fixture\AdapterContractProbe;
use PHPUnit\Framework\TestCase;

final class AdapterBuildArtifactTest extends TestCase
{
    public function testCanonicalEmptyArtifactHasExactSchemaAndIdentity(): void
    {
        $source = AdapterContractProbe::emptySourceSet();
        $profile = new AdapterProfileIdentity('vite/client.1');
        $toolchain = new ToolchainIdentity(str_repeat('a', 64));
        $artifact = (new AdapterBuildArtifactValidator())->create($source, $profile, $toolchain, 'kongono/asset-adapter-vite', new AdapterOutputSet($source, [], [], []));
        self::assertStringStartsWith('{"schema":"kongono.asset-adapter-build-artifact"', $artifact->bytes());
        self::assertSame(hash('sha256', $artifact->bytes()), $artifact->buildIdentity()->value());
    }
}
