<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Identity;

use Kongono\AssetAdapterContract\Identity\AdapterBuildIdentity;
use PHPUnit\Framework\TestCase;

final class AdapterBuildIdentityTest extends TestCase
{
    public function testIdentityUsesExactArtifactBytes(): void
    {
        self::assertSame(hash('sha256', "{}\n"), AdapterBuildIdentity::fromArtifactBytes("{}\n")->value());
    }
}
