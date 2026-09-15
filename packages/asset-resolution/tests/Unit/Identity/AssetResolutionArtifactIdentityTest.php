<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Unit\Identity;

use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;
use Kongono\AssetResolution\Identity\AssetResolutionArtifactIdentity;
use PHPUnit\Framework\TestCase;

final class AssetResolutionArtifactIdentityTest extends TestCase
{
    public function testIdentityDerivesFromExactBytes(): void
    {
        $identity = AssetResolutionArtifactIdentity::fromArtifactBytes("artifact\n");
        self::assertSame(hash('sha256', "artifact\n"), $identity->value());
        self::assertTrue($identity->equals(new AssetResolutionArtifactIdentity($identity->value())));
    }

    public function testIdentityRejectsNoncanonicalDigest(): void
    {
        $this->expectException(InvalidAssetResolutionException::class);
        new AssetResolutionArtifactIdentity(str_repeat('A', 64));
    }
}
