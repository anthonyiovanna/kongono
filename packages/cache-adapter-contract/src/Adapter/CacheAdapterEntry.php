<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Internal\AdapterContractGuard;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheFreshnessWindow;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Value\CacheValueBytes;

/** Carries one validated entry candidate without adapter-owned version evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheAdapterEntry
{
    public function __construct(
        private ContractVersion $contractVersion,
        private CacheKeyIdentity $keyIdentity,
        private CacheCodecIdentity $codecIdentity,
        private CacheValueBytes $valueBytes,
        private CacheContentIdentity $contentIdentity,
        private CacheFreshnessWindow $freshnessWindow,
    ) {
        AdapterContractGuard::entry($contractVersion, $valueBytes, $contentIdentity);
    }

    public function contractVersion(): ContractVersion
    {
        return $this->contractVersion;
    }

    public function keyIdentity(): CacheKeyIdentity
    {
        return $this->keyIdentity;
    }

    public function codecIdentity(): CacheCodecIdentity
    {
        return $this->codecIdentity;
    }

    public function valueBytes(): CacheValueBytes
    {
        return $this->valueBytes;
    }

    public function contentIdentity(): CacheContentIdentity
    {
        return $this->contentIdentity;
    }

    public function freshnessWindow(): CacheFreshnessWindow
    {
        return $this->freshnessWindow;
    }
}
