<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Unit\Build;

use Kongono\AssetBuild\Artifact\AssetBuildArtifactValidator;
use Kongono\AssetBuild\Build\AssetBuildResult;
use Kongono\AssetBuild\Build\NativeAssetBuilder;
use Kongono\AssetBuild\Exception\InvalidAssetBuildArtifactException;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetBuild\Tests\Fixture\BuildProbe;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\ContractVersion;
use PHPUnit\Framework\TestCase;

final class NativeAssetBuilderTest extends TestCase
{
    public function testBuildCopiesBytesAndDeduplicatesOnlyObjects(): void
    {
        $builder = new NativeAssetBuilder();
        $result = $builder->build(BuildProbe::sourceSet(scriptBytes: "body{}\n"));
        self::assertCount(2, $result->outputs());
        self::assertCount(1, $result->outputBytesByPath());
        self::assertSame("body{}\n", $result->outputs()[0]->bytes());
        self::assertTrue($result->equals($builder->build(BuildProbe::sourceSet(scriptBytes: "body{}\n"))));
    }

    public function testEmptySourceSetProducesCanonicalValidatedEmptyOutputMap(): void
    {
        $sourceSet = new AssetSourceSet(new AssetDeclarationSet(ContractVersion::Version1, [], [], []), []);
        $result = (new NativeAssetBuilder())->build($sourceSet);

        self::assertSame([], $result->outputs());
        self::assertSame([], $result->outputBytesByPath());
        self::assertStringContainsString('"outputs":[]', $result->artifact()->bytes());
        self::assertTrue((new AssetBuildArtifactValidator())->validateCurrent($sourceSet, $result->artifact()->bytes(), $result->outputBytesByPath())->equals($result->artifact()));
    }

    public function testResultRejectsUnreferencedOutputObjects(): void
    {
        $result = (new NativeAssetBuilder())->build(BuildProbe::sourceSet());
        $map = $result->outputBytesByPath();
        $map['objects/sha256/00/' . str_repeat('0', 64)] = '';
        $this->expectException(InvalidAssetBuildArtifactException::class);
        new AssetBuildResult($result->artifact(), $result->outputs(), $map);
    }
}
