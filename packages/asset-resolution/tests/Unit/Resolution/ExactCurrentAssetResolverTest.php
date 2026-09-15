<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Unit\Resolution;

use Kongono\AssetBuild\Build\NativeAssetBuilder;
use Kongono\AssetBuild\Source\AssetSource;
use Kongono\AssetBuild\Source\AssetSourcePath;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\EntryPoint\AssetEntryPoint;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Artifact\AssetResolutionArtifactFactory;
use Kongono\AssetResolution\Browser\PublicAssetBaseUrl;
use Kongono\AssetResolution\Exception\AssetNotFoundException;
use Kongono\AssetResolution\Exception\StaleAssetResolutionArtifactException;
use Kongono\AssetResolution\Identity\AssetResolutionArtifactIdentity;
use Kongono\AssetResolution\Policy\AssetCachePolicy;
use Kongono\AssetResolution\Policy\AssetIntegrityPolicy;
use Kongono\AssetResolution\Policy\AssetPreloadPolicy;
use Kongono\AssetResolution\Policy\AssetResolutionPolicy;
use Kongono\AssetResolution\Resolution\ExactCurrentAssetResolver;
use PHPUnit\Framework\TestCase;

final class ExactCurrentAssetResolverTest extends TestCase
{
    public function testResolutionIsExactCurrentAndPolicyOwned(): void
    {
        $identity = new LogicalAssetIdentity('app');
        $artifact = self::artifact($identity);
        $policy = new AssetResolutionPolicy(new PublicAssetBaseUrl('/assets/'), AssetCachePolicy::PrivateRevalidate, AssetIntegrityPolicy::RequireArtifactSha384, AssetPreloadPolicy::Omit);
        $resolver = new ExactCurrentAssetResolver();
        $result = $resolver->resolve($artifact, $artifact->identity(), $identity, $policy);
        self::assertSame($identity, $result->primary()->logicalIdentity());
        self::assertStringStartsWith('/assets/objects/sha256/', $result->primary()->publicUrl()->value());
        self::assertStringNotContainsString('app', $result->primary()->publicUrl()->value());
        self::assertSame('private, no-cache', $result->primary()->httpMetadata()->cacheControl());
        $browserIntegrity = $result->primary()->browserIntegrity();
        self::assertNotNull($browserIntegrity);
        self::assertStringStartsWith('sha384-', $browserIntegrity->value());
        self::assertSame([], $result->dependencies());
    }

    public function testStaleIdentityFailsBeforeLookup(): void
    {
        $artifact = self::artifact(new LogicalAssetIdentity('app'));
        $this->expectException(StaleAssetResolutionArtifactException::class);
        (new ExactCurrentAssetResolver())->resolve($artifact, new AssetResolutionArtifactIdentity(str_repeat('0', 64)), new LogicalAssetIdentity('missing'), self::policy());
    }

    public function testMissingIdentityHasNoFallback(): void
    {
        $artifact = self::artifact(new LogicalAssetIdentity('app'));
        $this->expectException(AssetNotFoundException::class);
        (new ExactCurrentAssetResolver())->resolve($artifact, $artifact->identity(), new LogicalAssetIdentity('missing'), self::policy());
    }

    private static function artifact(LogicalAssetIdentity $identity): \Kongono\AssetResolution\Artifact\AssetResolutionArtifact
    {
        $set = new AssetDeclarationSet(ContractVersion::Version1, [new AssetDeclaration(ContractVersion::Version1, $identity, AssetKind::Script, AssetContentType::TextJavascript)], [], [new AssetEntryPoint(ContractVersion::Version1, $identity)]);
        $sources = new AssetSourceSet($set, [new AssetSource($identity, new AssetSourcePath('src/app.js'), 'asset')]);

        return (new AssetResolutionArtifactFactory())->fromNative($sources, (new NativeAssetBuilder())->build($sources));
    }

    private static function policy(): AssetResolutionPolicy
    {
        return new AssetResolutionPolicy(new PublicAssetBaseUrl('/assets/'), AssetCachePolicy::NoStore, AssetIntegrityPolicy::Omit, AssetPreloadPolicy::Omit);
    }
}
