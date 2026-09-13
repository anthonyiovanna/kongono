<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewBuild\Exception\InvalidCompiledViewArtifactException;
use Kongono\ViewBuild\Source\ViewSourceIdentity;
use Kongono\ViewContract\Identity\CompiledViewIdentity;

/** Carries one completely validated metadata and generated-PHP evidence pair. */
#[Api(ApiStatus::Experimental)]
final readonly class CompiledViewArtifact
{
    public function __construct(
        private string $metadataBytes,
        private string $generatedPhpBytes,
        private CompiledViewIdentity $compiledViewIdentity,
        private ViewSourceIdentity $sourceIdentity,
        private string $modelSha256,
        private string $generatedPhpSha256,
        private string $generatedClass,
        private int $viewCount,
    ) {
        if (!hash_equals(hash('sha256', $metadataBytes), $compiledViewIdentity->value())
            || !hash_equals(hash('sha256', $generatedPhpBytes), $generatedPhpSha256)
            || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $modelSha256)
            || 1 !== preg_match('/\AKongono\\\\Generated\\\\View\\\\CompiledViewSet_[a-f0-9]{64}\z/D', $generatedClass)
            || $viewCount < 1 || $viewCount > 128) {
            throw InvalidCompiledViewArtifactException::invalid('artifact_identity_relation');
        }
    }

    public function metadataBytes(): string
    {
        return $this->metadataBytes;
    }
    public function generatedPhpBytes(): string
    {
        return $this->generatedPhpBytes;
    }
    public function compiledViewIdentity(): CompiledViewIdentity
    {
        return $this->compiledViewIdentity;
    }
    public function sourceIdentity(): ViewSourceIdentity
    {
        return $this->sourceIdentity;
    }
    public function modelSha256(): string
    {
        return $this->modelSha256;
    }
    public function generatedPhpSha256(): string
    {
        return $this->generatedPhpSha256;
    }
    public function generatedClass(): string
    {
        return $this->generatedClass;
    }
    public function viewCount(): int
    {
        return $this->viewCount;
    }

    public function equals(self $other): bool
    {
        return $this->metadataBytes === $other->metadataBytes
            && $this->generatedPhpBytes === $other->generatedPhpBytes
            && $this->compiledViewIdentity->equals($other->compiledViewIdentity)
            && $this->sourceIdentity->equals($other->sourceIdentity)
            && $this->modelSha256 === $other->modelSha256
            && $this->generatedPhpSha256 === $other->generatedPhpSha256
            && $this->generatedClass === $other->generatedClass
            && $this->viewCount === $other->viewCount;
    }
}
