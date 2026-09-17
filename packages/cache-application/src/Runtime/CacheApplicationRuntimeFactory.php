<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Adapter\CacheAdapter;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheApplication\Exception\CacheProfileMappingException;
use Kongono\CacheApplication\Exception\CacheRuntimeCompositionException;
use Kongono\CacheApplication\Mapping\CacheProfileCodecMapping;
use Kongono\CacheApplication\Workflow\CurrentCacheApplicationBuild;
use Kongono\CacheBuild\Artifact\CacheProfileArtifactValidator;
use Kongono\CacheRuntime\Binding\CacheRuntimeBinding;
use Kongono\CacheRuntime\Runtime\ExactCurrentCacheRuntime;
use Throwable;

/** Creates one exact-current runtime from caller-supplied mapping and adapter evidence. */
#[Api(ApiStatus::Experimental)]
final class CacheApplicationRuntimeFactory
{
    public function create(CurrentCacheApplicationBuild $current, CacheProfileCodecMapping $mapping, CacheAdapterIdentity $adapterIdentity, CacheAdapter $adapter): ExactCurrentCacheRuntime
    {
        try {
            $artifact = (new CacheProfileArtifactValidator())->validateCurrent($current->profiles(), $current->artifact()->bytes());
            $profile = $artifact->profiles()->profile($mapping->profile());
            if (null === $profile) {
                throw CacheProfileMappingException::mismatch('profile_missing');
            }
            if (!$profile->codec()->equals($mapping->codec())) {
                throw CacheProfileMappingException::mismatch('codec_mismatch');
            }
            return new ExactCurrentCacheRuntime(new CacheRuntimeBinding($current->profiles(), $artifact->bytes(), $mapping->profile(), $adapterIdentity, $adapter));
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof CacheProfileMappingException) {
                throw $failure;
            }
            throw CacheRuntimeCompositionException::failed($failure);
        }
    }
}
