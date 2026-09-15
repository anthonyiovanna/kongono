<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Adapter;

use Kongono\AssetAdapterContract\Adapter\AdapterBuildLimits;
use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;
use PHPUnit\Framework\TestCase;

final class AdapterBuildLimitsTest extends TestCase
{
    public function testBoundsAreExposedAndClosed(): void
    {
        $limits = new AdapterBuildLimits(600, 1_048_576, 1_048_576, 8_388_608, 2_048, 67_108_864, 134_217_728, 256);
        self::assertSame(600, $limits->timeoutSeconds());
        self::assertSame(256, $limits->outputDependencyDepth());
        $this->expectException(InvalidAssetAdapterContractException::class);
        new AdapterBuildLimits(0, 0, 0, 1, 0, 0, 0, 0);
    }
}
