<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Manifest;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Holds one ordinal canonical duplicate-free Vite manifest. */
#[Api(ApiStatus::Experimental)]final readonly class ViteManifest
{
    /** @var list<ViteManifestEntry> */
    private array $entries;

    /** @param list<ViteManifestEntry> $entries */
    public function __construct(array $entries)
    {
        $by = [];
        foreach ($entries as $entry) {
            $by[$entry->key()] = $entry;
        }ksort($by, SORT_STRING);
        $this->entries = array_values($by);
    }/** @return list<ViteManifestEntry> */public function entries(): array
    {
        return[...$this->entries];
    }public function entry(string $key): ?ViteManifestEntry
    {
        foreach ($this->entries as $entry) {
            if ($entry->key() === $key) {
                return$entry;
            }
        }return null;
    }public function count(): int
    {
        return count($this->entries);
    }

    /** @return list<string> */
    public function emittedFiles(): array
    {
        $files = [];
        foreach ($this->entries as $entry) {
            $files[$entry->file()] = true;
            foreach ([...$entry->css(),...$entry->assets()] as $file) {
                $files[$file] = true;
            }
        }$paths = array_keys($files);
        sort($paths, SORT_STRING);
        return$paths;
    }
}
