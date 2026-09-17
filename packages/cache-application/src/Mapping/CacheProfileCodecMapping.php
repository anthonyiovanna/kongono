<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Mapping;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheContract\Codec\CacheCodecIdentity;

/** Binds one selected profile to the exact inert codec identity promised by application code. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheProfileCodecMapping
{
    public function __construct(private CacheProfileName $profile, private CacheCodecIdentity $codec) {}
    public function profile(): CacheProfileName
    {
        return $this->profile;
    }
    public function codec(): CacheCodecIdentity
    {
        return $this->codec;
    }
}
