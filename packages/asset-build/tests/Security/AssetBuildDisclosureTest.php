<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Security;

use Kongono\AssetBuild\Build\NativeAssetBuilder;
use Kongono\AssetBuild\Exception\InvalidAssetSourceException;
use Kongono\AssetBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\TestCase;

final class AssetBuildDisclosureTest extends TestCase
{
    public function testArtifactAndFailuresDoNotDiscloseRawInputs(): void
    {
        $secret = 'private-browser-payload';
        $result = (new NativeAssetBuilder())->build(BuildProbe::sourceSet(scriptBytes: $secret));
        self::assertStringNotContainsString($secret, $result->artifact()->bytes());
        $exception = InvalidAssetSourceException::invalid($secret, entryCount: 1);
        self::assertStringNotContainsString($secret, $exception->getMessage());
        self::assertStringNotContainsString($secret, (string) $exception);
    }
}
