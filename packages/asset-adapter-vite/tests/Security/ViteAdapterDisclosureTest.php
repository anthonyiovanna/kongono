<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Security;

use Kongono\AssetAdapterVite\Exception\ViteBuildExecutionException;
use PHPUnit\Framework\TestCase;

final class ViteAdapterDisclosureTest extends TestCase
{
    public function testRawProcessAndPathMaterialNeverEnterPresentation(): void
    {
        $secret = 'C:/private/workspace raw stderr';
        $failure = ViteBuildExecutionException::failed($secret, new \RuntimeException($secret));
        self::assertStringNotContainsString($secret, $failure->getMessage());
        self::assertStringNotContainsString($secret, (string) $failure);
        self::assertSame($secret, $failure->getPrevious()?->getMessage());
    }
}
