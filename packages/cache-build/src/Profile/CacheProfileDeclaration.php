<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheNamespace;

/** Binds one inert bounded Version 1 cache-profile declaration. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheProfileDeclaration
{
    private ContractVersion $contractVersion;

    public function __construct(
        ContractVersion $contractVersion,
        private CacheProfileName $name,
        private CacheNamespace $namespace,
        private CacheCodecIdentity $codec,
        private CacheLifetime $lifetime,
        private CacheProfileValueLimit $valueLimit,
    ) {
        $this->contractVersion = match ($contractVersion) {
            ContractVersion::Version1 => $contractVersion,
        };
    }

    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
    }

    public function name(): CacheProfileName
    {
        return $this->name;
    }

    public function namespace(): CacheNamespace
    {
        return $this->namespace;
    }

    public function codec(): CacheCodecIdentity
    {
        return $this->codec;
    }

    public function lifetime(): CacheLifetime
    {
        return $this->lifetime;
    }

    public function valueLimit(): CacheProfileValueLimit
    {
        return $this->valueLimit;
    }

    public function equals(self $other): bool
    {
        return $this->name->equals($other->name)
            && $this->namespace->equals($other->namespace)
            && $this->codec->equals($other->codec)
            && $this->lifetime->equals($other->lifetime)
            && $this->valueLimit->equals($other->valueLimit);
    }
}
