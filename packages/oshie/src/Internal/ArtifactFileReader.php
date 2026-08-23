<?php

declare(strict_types=1);

namespace Kongono\Oshie\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactValidator;
use Kongono\Knowledge\Exception\InvalidKnowledgeArtifactException;
use Kongono\Oshie\Diagnostic\OshieDiagnostic;
use Kongono\Oshie\Exception\ArtifactUnavailableException;
use Kongono\Oshie\Exception\InvalidArtifactException;

/** Reads one explicit content-addressed artifact through a bounded local-file gate. */
#[Api(ApiStatus::Internal)]
final class ArtifactFileReader
{
    public function read(string $path): string
    {
        if ('' === $path || str_contains($path, "\0")) {
            throw new ArtifactUnavailableException(OshieDiagnostic::ArtifactUnavailable, 'artifact-path');
        }
        $withoutDrive = 1 === preg_match('/^[A-Za-z]:/', $path) ? substr($path, 2) : $path;
        if (str_contains($withoutDrive, ':')) {
            throw new InvalidArtifactException(OshieDiagnostic::InvalidArtifact, 'artifact-name');
        }
        $parent = dirname($path);
        $resolvedParent = realpath($parent);
        $resolved = realpath($path);
        if (false === $resolvedParent || !is_dir($resolvedParent) || is_link($parent) || false === $resolved || !is_file($path) || is_link($path)) {
            throw new ArtifactUnavailableException(OshieDiagnostic::ArtifactUnavailable, 'artifact-path');
        }
        if ($this->normalize($resolved) !== $this->normalize($path) || $this->normalize(dirname($resolved)) !== $this->normalize($resolvedParent)) {
            throw new InvalidArtifactException(OshieDiagnostic::InvalidArtifact, 'artifact-path');
        }
        if (1 !== preg_match('/^kongono-framework-knowledge-v1-([a-f0-9]{64})\.json$/D', basename($path), $match)) {
            throw new InvalidArtifactException(OshieDiagnostic::InvalidArtifact, 'artifact-name');
        }
        $before = @stat($path);
        if (false === $before) {
            throw new ArtifactUnavailableException(OshieDiagnostic::ArtifactUnavailable, 'artifact-read');
        }
        if ($before['size'] > FrameworkKnowledgeArtifactGenerator::MAX_ARTIFACT_BYTES) {
            throw new InvalidArtifactException(OshieDiagnostic::InvalidArtifact, 'artifact-metadata');
        }
        $handle = @fopen($path, 'rb');
        if (false === $handle) {
            throw new ArtifactUnavailableException(OshieDiagnostic::ArtifactUnavailable, 'artifact-read');
        }
        $bytes = '';
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, max(1, min(8192, FrameworkKnowledgeArtifactGenerator::MAX_ARTIFACT_BYTES + 1 - strlen($bytes))));
                if (false === $chunk) {
                    throw new ArtifactUnavailableException(OshieDiagnostic::ArtifactUnavailable, 'artifact-read');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > FrameworkKnowledgeArtifactGenerator::MAX_ARTIFACT_BYTES) {
                    throw new InvalidArtifactException(OshieDiagnostic::InvalidArtifact, 'artifact-metadata');
                }
            }
            $after = fstat($handle);
        } finally {
            fclose($handle);
        }
        if (false === $after || !$this->sameMetadata($before, $after) || strlen($bytes) !== $before['size']) {
            throw new ArtifactUnavailableException(OshieDiagnostic::ArtifactUnavailable, 'artifact-read');
        }
        try {
            $artifact = (new FrameworkKnowledgeArtifactValidator())->validateStructure($bytes);
        } catch (InvalidKnowledgeArtifactException $exception) {
            throw new InvalidArtifactException(OshieDiagnostic::InvalidArtifact, 'artifact-shape', $exception);
        }
        if ($match[1] !== $artifact->artifactSha256()) {
            throw new InvalidArtifactException(OshieDiagnostic::InvalidArtifact, 'artifact-name');
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
