<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Resolution;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Artifact\AssetResolutionArtifact;
use Kongono\AssetResolution\Artifact\AssetResolutionObject;
use Kongono\AssetResolution\Browser\BrowserIntegrity;
use Kongono\AssetResolution\Browser\PublicAssetUrl;
use Kongono\AssetResolution\Exception\AssetNotFoundException;
use Kongono\AssetResolution\Exception\StaleAssetResolutionArtifactException;
use Kongono\AssetResolution\Http\AssetHttpMetadata;
use Kongono\AssetResolution\Identity\AssetResolutionArtifactIdentity;
use Kongono\AssetResolution\Policy\AssetIntegrityPolicy;
use Kongono\AssetResolution\Policy\AssetPreloadPolicy;
use Kongono\AssetResolution\Policy\AssetResolutionPolicy;

/** Resolves one identity from one exact artifact without effects or retained state. */
#[Api(ApiStatus::Experimental)]
final class ExactCurrentAssetResolver
{
    public function resolve(AssetResolutionArtifact $artifact, AssetResolutionArtifactIdentity $expectedArtifactIdentity, LogicalAssetIdentity $identity, AssetResolutionPolicy $policy): ResolvedAssetSet
    {
        if (!$artifact->identity()->equals($expectedArtifactIdentity)) {
            throw StaleAssetResolutionArtifactException::stale();
        }
        $entry = $artifact->entry($identity);
        if (null === $entry) {
            throw AssetNotFoundException::missing();
        }
        $dependencies = [];
        foreach ($entry->dependencies() as $dependency) {
            $dependencies[] = self::resolved($dependency['object'], null, $policy);
        }

        return new ResolvedAssetSet(self::resolved($entry->primary(), $entry->identity(), $policy), $dependencies);
    }

    private static function resolved(AssetResolutionObject $object, ?LogicalAssetIdentity $logicalIdentity, AssetResolutionPolicy $policy): ResolvedAsset
    {
        $integrity = AssetIntegrityPolicy::RequireArtifactSha384 === $policy->integrityPolicy() ? new BrowserIntegrity($object->integrity()) : null;
        $preload = AssetPreloadPolicy::HonorArtifact === $policy->preloadPolicy() ? $object->preloadHint() : PreloadHint::None;

        return new ResolvedAsset(
            $logicalIdentity,
            new PublicAssetUrl($policy->publicBaseUrl(), $object->objectKey()),
            $object->contentIdentity(),
            $object->kind(),
            $object->contentType(),
            new AssetHttpMetadata($object->contentType(), $object->byteCount(), $object->contentIdentity(), $policy->cachePolicy()),
            $integrity,
            $preload,
        );
    }
}
