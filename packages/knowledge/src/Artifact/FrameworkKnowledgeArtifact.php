<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Knowledge\Source\KnowledgeSource;

/** Stores one detached canonical artifact and its exact derived identities. */
#[Api(ApiStatus::Experimental)]
final readonly class FrameworkKnowledgeArtifact
{
    /** @var list<KnowledgeSource> */
    private array $sources;

    /** @var array<string, KnowledgeSource> */
    private array $sourcesByReference;

    /** @param list<KnowledgeSource> $sources */
    public function __construct(
        private string $canonicalBytes,
        private string $artifactSha256,
        private string $sourceSetSha256,
        private string $generatorImplementationSha256,
        private int $sourceCount,
        private int $sourceBytes,
        array $sources,
    ) {
        $this->sources = [...$sources];
        $byReference = [];
        foreach ($sources as $source) {
            $byReference[$source->reference()] = $source;
        }
        $this->sourcesByReference = $byReference;
    }

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function artifactSha256(): string
    {
        return $this->artifactSha256;
    }

    public function sourceSetSha256(): string
    {
        return $this->sourceSetSha256;
    }

    public function generatorImplementationSha256(): string
    {
        return $this->generatorImplementationSha256;
    }

    public function sourceCount(): int
    {
        return $this->sourceCount;
    }

    public function sourceBytes(): int
    {
        return $this->sourceBytes;
    }

    /** @return list<KnowledgeSource> */
    public function sources(): array
    {
        return [...$this->sources];
    }

    public function sourceByReference(string $reference): ?KnowledgeSource
    {
        return $this->sourcesByReference[$reference] ?? null;
    }
}
