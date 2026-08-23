<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Knowledge\Diagnostic\KnowledgeDiagnostic;
use Kongono\Knowledge\Exception\KnowledgePublicationException;

/** Publishes one validated artifact by content address without overwriting. */
#[Api(ApiStatus::Experimental)]
final class FrameworkKnowledgeArtifactPublisher
{
    public function publish(FrameworkKnowledgeArtifact $artifact, string $outputDirectory): string
    {
        (new FrameworkKnowledgeArtifactValidator())->validateStructure($artifact->canonicalBytes());
        $resolvedDirectory = realpath($outputDirectory);
        if (false === $resolvedDirectory || !is_dir($resolvedDirectory) || is_link($outputDirectory) || is_link($resolvedDirectory)) {
            throw new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, 'output_directory');
        }

        $basename = sprintf('kongono-framework-knowledge-v1-%s.json', $artifact->artifactSha256());
        $target = $resolvedDirectory . DIRECTORY_SEPARATOR . $basename;
        if (file_exists($target) || is_link($target)) {
            $this->acceptExisting($target, $artifact);

            return $basename;
        }

        $temporary = tempnam($resolvedDirectory, '.kongono-knowledge-');
        if (false === $temporary || realpath(dirname($temporary)) !== $resolvedDirectory || is_link($temporary)) {
            throw new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, 'temporary_create');
        }

        $published = false;
        try {
            $handle = @fopen($temporary, 'c+b');
            if (false === $handle || !ftruncate($handle, 0)) {
                throw new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, 'temporary_write');
            }
            try {
                $offset = 0;
                $bytes = $artifact->canonicalBytes();
                while ($offset < strlen($bytes)) {
                    $written = fwrite($handle, substr($bytes, $offset));
                    if (false === $written || 0 === $written) {
                        throw new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, 'temporary_write');
                    }
                    $offset += $written;
                }
                if (!fflush($handle)) {
                    throw new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, 'temporary_flush');
                }
                if (!fsync($handle)) {
                    throw new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, 'temporary_sync');
                }
            } finally {
                fclose($handle);
            }

            if (!@link($temporary, $target)) {
                if (file_exists($target)) {
                    $this->acceptExisting($target, $artifact);
                    $published = true;
                } else {
                    throw new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, 'atomic_link');
                }
            } else {
                $published = true;
            }
        } finally {
            $removed = @unlink($temporary);
            if (!$removed && file_exists($temporary) && $published) {
                throw new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, 'temporary_cleanup');
            }
        }

        return $basename;
    }

    private function acceptExisting(string $target, FrameworkKnowledgeArtifact $artifact): void
    {
        if (!is_file($target) || is_link($target)) {
            throw new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, 'existing_target_invalid');
        }
        $bytes = file_get_contents($target);
        if (false === $bytes || $bytes !== $artifact->canonicalBytes()) {
            throw new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, 'target_conflict');
        }
        try {
            (new FrameworkKnowledgeArtifactValidator())->validateStructure($bytes);
        } catch (\Throwable $exception) {
            throw new KnowledgePublicationException(KnowledgeDiagnostic::PublicationFailed, 'existing_target_invalid', $exception);
        }
    }
}
