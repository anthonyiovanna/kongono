<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Unit\Policy;

use Kongono\AssetResolution\Browser\PublicAssetBaseUrl;
use Kongono\AssetResolution\Policy\AssetCachePolicy;
use Kongono\AssetResolution\Policy\AssetIntegrityPolicy;
use Kongono\AssetResolution\Policy\AssetPreloadPolicy;
use Kongono\AssetResolution\Policy\AssetResolutionPolicy;
use PHPUnit\Framework\TestCase;

final class AssetResolutionPolicyTest extends TestCase
{
    public function testPolicyContainsExactlyTheFourExplicitChoices(): void
    {
        $policy = new AssetResolutionPolicy(new PublicAssetBaseUrl('/assets/'), AssetCachePolicy::NoStore, AssetIntegrityPolicy::Omit, AssetPreloadPolicy::HonorArtifact);
        self::assertSame('/assets/', $policy->publicBaseUrl()->value());
        self::assertSame('no-store', $policy->cachePolicy()->value);
        self::assertSame(AssetIntegrityPolicy::Omit, $policy->integrityPolicy());
        self::assertSame(AssetPreloadPolicy::HonorArtifact, $policy->preloadPolicy());
    }
}
