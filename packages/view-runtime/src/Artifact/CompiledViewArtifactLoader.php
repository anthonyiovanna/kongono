<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewRuntime\Exception\ArtifactLoadException;
use Kongono\ViewRuntime\Exception\StaleRuntimeArtifactException;
use Kongono\ViewRuntime\Internal\GeneratedProgramLoader;
use Kongono\ViewRuntime\Internal\RuntimeGuard;
use Kongono\ViewRuntime\Internal\StableArtifactReader;

/** Loads one explicit contained artifact matching caller-selected current identity. */
#[Api(ApiStatus::Experimental)]
final class CompiledViewArtifactLoader
{
    public function load(string $artifactRoot, string $artifactDirectory, CompiledViewIdentity $expectedIdentity): LoadedCompiledViewArtifact
    {
        $reader = new StableArtifactReader();
        $paths = $reader->paths($artifactRoot, $artifactDirectory, $expectedIdentity->value());
        $metadataRead = $reader->read($paths['metadata']);
        if (!hash_equals(hash('sha256', $metadataRead['bytes']), $expectedIdentity->value())) {
            throw StaleRuntimeArtifactException::mismatch(sha256: hash('sha256', $metadataRead['bytes']));
        }
        $metadata = RuntimeGuard::metadata($metadataRead['bytes']);
        $generatedRead = $reader->read($paths['generated']);
        RuntimeGuard::generated($generatedRead['bytes'], $metadata['generated_class'], $metadata['generated_sha256']);
        $emit = (new GeneratedProgramLoader())->load($paths['generated'], $metadata['generated_class'], $metadata['source_sha256'], $metadata['model_sha256'], $metadata['generated_sha256']);
        $metadataAfter = $reader->read($paths['metadata']);
        $generatedAfter = $reader->read($paths['generated']);
        if (!hash_equals($metadataRead['bytes'], $metadataAfter['bytes']) || !hash_equals($generatedRead['bytes'], $generatedAfter['bytes'])) {
            throw ArtifactLoadException::failed('artifact_changed_after_load', $metadata['generated_sha256']);
        }

        return RuntimeGuard::loaded($expectedIdentity, $metadata['source_sha256'], $metadata['model_sha256'], $metadata['generated_sha256'], $metadata['generated_class'], $metadata['view_count'], $metadata['view_schemas'], $emit);
    }
}
