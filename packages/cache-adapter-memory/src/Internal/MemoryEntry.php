<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterMemory\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\Entry\CacheEntryEvidence;
use Kongono\CacheContract\Freshness\CacheFreshnessWindow;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;

/** Retains one private immutable generation and creates detached public evidence. */
#[Api(ApiStatus::Internal)]
final readonly class MemoryEntry
{
    public function __construct(
        private CacheAdapterEntry $entry,
        private CacheEntryVersion $version,
    ) {}

    public function keyIdentity(): CacheKeyIdentity
    {
        return new CacheKeyIdentity($this->entry->keyIdentity()->digest());
    }

    public function versionEquals(CacheEntryVersion $version): bool
    {
        return $this->version->equals($version);
    }

    public function valueByteCount(): int
    {
        return $this->entry->valueBytes()->byteCount();
    }

    public function evidence(): CacheEntryEvidence
    {
        $freshness = $this->entry->freshnessWindow();

        return new CacheEntryEvidence(
            $this->entry->contractVersion(),
            $this->keyIdentity(),
            new CacheCodecIdentity($this->entry->codecIdentity()->name(), $this->entry->codecIdentity()->version()),
            new CacheValueBytes($this->entry->valueBytes()->bytes()),
            new CacheContentIdentity($this->entry->contentIdentity()->digest()),
            new CacheFreshnessWindow(
                new CacheInstant($freshness->storedAt()->microseconds()),
                new CacheInstant($freshness->expiresAt()->microseconds()),
            ),
            new CacheEntryVersion($this->version->token()),
        );
    }
}
