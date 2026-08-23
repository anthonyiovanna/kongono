<?php

declare(strict_types=1);

namespace Kongono\Chie\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Chie\Diagnostic\ChieDiagnostic;
use Kongono\Chie\Exception\ArtifactUnavailableException;
use Kongono\Chie\Exception\InvalidArtifactException;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactValidator;
use Kongono\Knowledge\Exception\InvalidKnowledgeArtifactException;

/** Reads one explicit content-addressed artifact through a bounded local-file gate. */
#[Api(ApiStatus::Internal)]
final class ArtifactFileReader
{
    public function read(string $path): string
    {
        if ('' === $path || str_contains($path, "\0")) {
            throw new ArtifactUnavailableException(ChieDiagnostic::ArtifactUnavailable, 'artifact-path');
        }
        $withoutDrive = 1 === preg_match('/^[A-Za-z]:/', $path) ? substr($path, 2) : $path;
        if (str_contains($withoutDrive, ':')) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'artifact-name');
        }
        $parent = dirname($path);
        $resolvedParent = realpath($parent);
        $resolved = realpath($path);
        if (false === $resolvedParent || !is_dir($resolvedParent) || is_link($parent) || false === $resolved || !is_file($path) || is_link($path)) {
            throw new ArtifactUnavailableException(ChieDiagnostic::ArtifactUnavailable, 'artifact-path');
        }
        if ($this->normalize($resolved) !== $this->normalize($path) || $this->normalize(dirname($resolved)) !== $this->normalize($resolvedParent)) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'artifact-path');
        }
        $basename = basename($path);
        if (1 !== preg_match('/^kongono-framework-knowledge-v1-([a-f0-9]{64})\.json$/D', $basename, $match)) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'artifact-name');
        }
        $before = @stat($path);
        if (false === $before) {
            throw new ArtifactUnavailableException(ChieDiagnostic::ArtifactUnavailable, 'artifact-read');
        }
        if ($before['size'] > FrameworkKnowledgeArtifactGenerator::MAX_ARTIFACT_BYTES) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'artifact-metadata');
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw new ArtifactUnavailableException(ChieDiagnostic::ArtifactUnavailable, 'artifact-read');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, FrameworkKnowledgeArtifactGenerator::MAX_ARTIFACT_BYTES + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw new ArtifactUnavailableException(ChieDiagnostic::ArtifactUnavailable, 'artifact-read');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > FrameworkKnowledgeArtifactGenerator::MAX_ARTIFACT_BYTES) {
                    throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'artifact-metadata');
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw new ArtifactUnavailableException(ChieDiagnostic::ArtifactUnavailable, 'artifact-read');
        }
        try {
            $artifact = (new FrameworkKnowledgeArtifactValidator())->validateStructure($bytes);
        } catch (InvalidKnowledgeArtifactException $exception) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'artifact-shape', $exception);
        }
        if ($match[1] !== $artifact->artifactSha256()) {
            throw new InvalidArtifactException(ChieDiagnostic::InvalidArtifact, 'artifact-name');
        }

        return $bytes;
    }

    /**
     * @param array<string|int, mixed> $before
     * @param array<string|int, mixed> $after
     */
    private function sameMetadata(array $before, array $after): bool
    {
        foreach (['dev', 'ino', 'mode', 'size', 'mtime', 'ctime'] as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
