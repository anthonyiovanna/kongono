<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetBuild\Exception\InvalidAssetSourceException;
use Kongono\AssetBuild\Internal\BuildGuard;
use Kongono\AssetBuild\Internal\CanonicalJson;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;

/** Holds one detached, complete, bounded, ordinally canonical asset source set. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetSourceSet
{
    /** @var list<AssetSource> */
    private array $sources;
    private AssetSourceSetIdentity $identity;

    /** @param array<array-key, mixed> $sources */
    public function __construct(private AssetDeclarationSet $declarationSet, array $sources)
    {
        if (!array_is_list($sources)) {
            throw InvalidAssetSourceException::invalid('sources_not_list', entryCount: count($sources));
        }
        if (count($sources) > 1024) {
            throw InvalidAssetSourceException::invalid('source_count', entryCount: count($sources), limit: 1024);
        }
        $byIdentity = [];
        $paths = [];
        $aggregate = 0;
        foreach ($sources as $position => $source) {
            if (!$source instanceof AssetSource) {
                throw InvalidAssetSourceException::invalid('invalid_source_entry', entryCount: count($sources), entryPosition: $position);
            }
            $identity = $source->identity()->value();
            if (isset($byIdentity[$identity])) {
                throw InvalidAssetSourceException::invalid('duplicate_logical_identity', entryPosition: $position);
            }
            if (isset($paths[$source->path()->value()])) {
                throw InvalidAssetSourceException::invalid('duplicate_source_path', entryPosition: $position);
            }
            if (null === $declarationSet->declaration($source->identity())) {
                throw InvalidAssetSourceException::invalid('extra_source', entryPosition: $position);
            }
            $aggregate += $source->byteCount();
            if ($aggregate > 134_217_728) {
                throw InvalidAssetSourceException::invalid('aggregate_bytes', entryCount: count($sources), limit: 134_217_728);
            }
            $byIdentity[$identity] = $source;
            $paths[$source->path()->value()] = true;
        }
        foreach ($declarationSet->declarations() as $declaration) {
            if (!isset($byIdentity[$declaration->identity()->value()])) {
                throw InvalidAssetSourceException::invalid('missing_source', entryCount: count($sources));
            }
        }
        ksort($byIdentity, SORT_STRING);
        $this->sources = array_values($byIdentity);
        $sourceModel = array_map(static fn(AssetSource $source): array => [
            'identity' => $source->identity()->value(),
            'source_path' => $source->path()->value(),
            'content_sha256' => $source->contentIdentity()->value(),
            'byte_count' => $source->byteCount(),
        ], $this->sources);
        $bytes = CanonicalJson::encode([
            'contract_version' => $declarationSet->contractVersion()->value,
            'declarations' => BuildGuard::declarations($declarationSet),
            'dependencies' => BuildGuard::dependencies($declarationSet),
            'entry_points' => BuildGuard::entryPoints($declarationSet),
            'sources' => $sourceModel,
        ]);
        $this->identity = new AssetSourceSetIdentity(hash('sha256', $bytes));
    }

    public function declarationSet(): AssetDeclarationSet
    {
        return $this->declarationSet;
    }
    /** @return list<AssetSource> */
    public function sources(): array
    {
        return [...$this->sources];
    }
    public function sourceCount(): int
    {
        return count($this->sources);
    }
    public function aggregateByteCount(): int
    {
        return array_sum(array_map(static fn(AssetSource $source): int => $source->byteCount(), $this->sources));
    }
    public function identity(): AssetSourceSetIdentity
    {
        return $this->identity;
    }

    public function source(LogicalAssetIdentity $identity): ?AssetSource
    {
        foreach ($this->sources as $source) {
            if ($source->identity()->equals($identity)) {
                return $source;
            }
        }

        return null;
    }

    public function equals(self $other): bool
    {
        if (!$this->declarationSet->equals($other->declarationSet) || count($this->sources) !== count($other->sources)) {
            return false;
        }
        foreach ($this->sources as $position => $source) {
            if (!$source->equals($other->sources[$position])) {
                return false;
            }
        }

        return true;
    }
}
