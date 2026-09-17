<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Entry;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheFreshnessWindow;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Internal\ContractGuard;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;

/** Carries detached immutable cache entry evidence without currentness claims. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheEntryEvidence
{
    public function __construct(
        private ContractVersion $contractVersion,
        private CacheKeyIdentity $keyIdentity,
        private CacheCodecIdentity $codecIdentity,
        private CacheValueBytes $valueBytes,
        private CacheContentIdentity $contentIdentity,
        private CacheFreshnessWindow $freshnessWindow,
        private CacheEntryVersion $entryVersion,
    ) {
        ContractGuard::entryEvidence($contractVersion, $valueBytes, $contentIdentity);
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

    public function entryVersion(): CacheEntryVersion
    {
        return $this->entryVersion;
    }

    public function equals(self $other): bool
    {
        return ContractGuard::sameContractVersion($this->contractVersion, $other->contractVersion)
            && $this->keyIdentity->equals($other->keyIdentity)
            && $this->codecIdentity->equals($other->codecIdentity)
            && $this->valueBytes->equals($other->valueBytes)
            && $this->contentIdentity->equals($other->contentIdentity)
            && $this->freshnessWindow->equals($other->freshnessWindow)
            && $this->entryVersion->equals($other->entryVersion);
    }
}
