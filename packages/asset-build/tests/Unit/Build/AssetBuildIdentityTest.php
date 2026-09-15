<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Unit\Build;

use Kongono\AssetBuild\Build\AssetBuildIdentity;
use PHPUnit\Framework\TestCase;

final class AssetBuildIdentityTest extends TestCase
{
    public function testIdentityIsExactArtifactHash(): void
    {
        $identity = AssetBuildIdentity::fromArtifactBytes("{}\n");
        self::assertSame(hash('sha256', "{}\n"), $identity->value());
        self::assertTrue($identity->equals(new AssetBuildIdentity($identity->value())));
    }
}
