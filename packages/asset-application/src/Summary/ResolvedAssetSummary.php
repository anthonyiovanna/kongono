<?php

declare(strict_types=1);

namespace Kongono\AssetApplication\Summary;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetResolution\Resolution\ResolvedAssetSet;

/** Exposes bounded resolved metadata without source output or private paths. */
#[Api(ApiStatus::Experimental)]
final readonly class ResolvedAssetSummary
{
    private const string SCHEMA = 'kongono.asset-resolution-summary';
    private const int VERSION = 1;

    public function __construct(
        private string $logicalIdentity,
        private string $publicUrl,
        private string $contentIdentity,
        private string $kind,
        private string $contentType,
        private int $byteCount,
        private string $cacheControl,
        private string $etag,
        private ?string $integrity,
        private string $preload,
        private int $dependencyCount,
        private ?string $executionIntent,
    ) {}

    public static function fromResolved(ResolvedAssetSet $resolved, ?string $intent = null): self
    {
        $primary = $resolved->primary();

        return new self(
            (string) $primary->logicalIdentity()?->value(),
            $primary->publicUrl()->value(),
            $primary->contentIdentity()->value(),
            $primary->kind()->value,
            $primary->contentType()->value,
            $primary->httpMetadata()->contentLength(),
            $primary->httpMetadata()->cacheControl(),
            $primary->httpMetadata()->etag(),
            $primary->browserIntegrity()?->value(),
            $primary->preloadHint()->value,
            count($resolved->dependencies()),
            $intent,
        );
    }

    public function schema(): string
    {
        return self::SCHEMA;
    }
    public function version(): int
    {
        return self::VERSION;
    }
    public function logicalIdentity(): string
    {
        return $this->logicalIdentity;
    }
    public function publicUrl(): string
    {
        return $this->publicUrl;
    }
    public function contentIdentity(): string
    {
        return $this->contentIdentity;
    }
    public function kind(): string
    {
        return $this->kind;
    }
    public function contentType(): string
    {
        return $this->contentType;
    }
    public function byteCount(): int
    {
        return $this->byteCount;
    }
    public function cacheControl(): string
    {
        return $this->cacheControl;
    }
    public function etag(): string
    {
        return $this->etag;
    }
    public function integrity(): ?string
    {
        return $this->integrity;
    }
    public function preload(): string
    {
        return $this->preload;
    }
    public function dependencyCount(): int
    {
        return $this->dependencyCount;
    }
    public function executionIntent(): ?string
    {
        return $this->executionIntent;
    }
}
