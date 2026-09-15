<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Manifest;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Carries one detached closed producer-manifest row for operation-local translation. */
#[Api(ApiStatus::Experimental)]final readonly class ViteManifestEntry
{
    /**
     * @param list<string> $imports
     * @param list<string> $dynamicImports
     * @param list<string> $css
     * @param list<string> $assets
     */
    public function __construct(private string $key, private string $file, private ?string $source, private ?string $name, private bool $entry, private bool $dynamicEntry, private array $imports, private array $dynamicImports, private array $css, private array $assets) {}
    public function key(): string
    {
        return$this->key;
    }public function file(): string
    {
        return$this->file;
    }public function source(): ?string
    {
        return$this->source;
    }public function name(): ?string
    {
        return$this->name;
    }public function isEntry(): bool
    {
        return$this->entry;
    }public function isDynamicEntry(): bool
    {
        return$this->dynamicEntry;
    }/** @return list<string> */public function imports(): array
    {
        return[...$this->imports];
    }/** @return list<string> */public function dynamicImports(): array
    {
        return[...$this->dynamicImports];
    }/** @return list<string> */public function css(): array
    {
        return[...$this->css];
    }/** @return list<string> */public function assets(): array
    {
        return[...$this->assets];
    }
}
