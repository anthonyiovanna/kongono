<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Unit\Browser;

use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetResolution\Browser\BrowserIntegrity;
use Kongono\AssetResolution\Exception\InvalidAssetResolutionException;
use PHPUnit\Framework\TestCase;

final class BrowserIntegrityTest extends TestCase
{
    public function testSha384HexBecomesExactBase64Sri(): void
    {
        $hex = hash('sha384', 'asset');
        self::assertSame('sha384-' . base64_encode((string) hex2bin($hex)), (new BrowserIntegrity(new AssetIntegrity(IntegrityAlgorithm::Sha384, $hex)))->value());
    }

    public function testOtherAlgorithmsFailClosed(): void
    {
        $this->expectException(InvalidAssetResolutionException::class);
        new BrowserIntegrity(new AssetIntegrity(IntegrityAlgorithm::Sha256, hash('sha256', 'asset')));
    }
}
