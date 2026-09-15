<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Unit\Build;

use Kongono\AssetBuild\Build\NativeAssetBuilder;
use Kongono\AssetBuild\Tests\Fixture\BuildProbe;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use PHPUnit\Framework\TestCase;

final class AssetOutputTest extends TestCase
{
    public function testOutputPathIntegrityAndBytesRemainExact(): void
    {
        $output = (new NativeAssetBuilder())->build(BuildProbe::sourceSet())->outputs()[0];
        self::assertSame($output->contentIdentity()->value(), $output->path()->digest());
        self::assertSame(IntegrityAlgorithm::Sha384, $output->integrity()->algorithm());
        self::assertSame(hash('sha384', $output->bytes()), $output->integrity()->digest());
        self::assertSame(PreloadHint::None, $output->preloadHint());
    }
}
