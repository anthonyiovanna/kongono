<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Represents one immutable whole source embedded in an exact artifact. */
#[Api(ApiStatus::Experimental)]
final readonly class KnowledgeSource
{
    public function __construct(
        private string $reference,
        private int $ordinal,
        private string $path,
        private KnowledgeSourceAuthority $authority,
        private int $bytes,
        private string $sha256,
        private string $content,
    ) {}

    public function reference(): string
    {
        return $this->reference;
    }

    public function ordinal(): int
    {
        return $this->ordinal;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function authority(): KnowledgeSourceAuthority
    {
        return $this->authority;
    }

    public function bytes(): int
    {
        return $this->bytes;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }

    public function content(): string
    {
        return $this->content;
    }
}
