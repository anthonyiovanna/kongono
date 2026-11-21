<?php

declare(strict_types=1);

namespace Kongono\Storage;

final readonly class ObjectMeta
{
    /** @param array<string, string> $metadata */
    public function __construct(
        public string $key,
        public int $size,
        public string $sha256,
        public string $version,
        public string $contentType,
        public string $updatedAt,
        public array $metadata = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'size' => $this->size,
            'sha256' => $this->sha256,
            'version' => $this->version,
            'content_type' => $this->contentType,
            'updated_at' => $this->updatedAt,
            'metadata' => $this->metadata,
        ];
    }
}
