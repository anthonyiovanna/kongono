<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Build;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Exception\InvalidMappingSourceException;
use Kongono\DatabaseMapping\Internal\CanonicalJson;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;
use Kongono\DatabaseMapping\Internal\MappingImplementation;
use Kongono\DatabaseMapping\Internal\MappingModelCompiler;
use Kongono\DatabaseMapping\Internal\PathGuard;

/** Validates one explicit mapping source and builds detached canonical evidence. */
#[Api(ApiStatus::Experimental)]
final class MappingArtifactBuilder
{
    public const MAX_ARTIFACT_BYTES = 16_777_216;
    public const BUILDER_CONTRACT_VERSION = 1;

    public function build(string $applicationRoot, string $sourceRelativePath): MappingArtifact
    {
        $root = PathGuard::resolveApplicationRoot($applicationRoot);
        $sourceSnapshot = PathGuard::readSource($root, $sourceRelativePath);
        PathGuard::validateSourceText($sourceSnapshot['bytes']);
        try {
            $source = CanonicalJson::decodeObject($sourceSnapshot['bytes'], 24);
        } catch (JsonException) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::SourceJson);
        }
        $compiled = (new MappingModelCompiler())->compile($root, $source);
        $modelBytes = CanonicalJson::encode($compiled['model']);
        $modelSha256 = hash('sha256', $modelBytes);

        $composerSnapshot = PathGuard::readProjectFile($root, 'composer.json');
        $lockSnapshot = PathGuard::readProjectFile($root, 'composer.lock');
        $sourceSetRecords = [];
        $sourceBytes = 0;
        foreach ($compiled['sources'] as $record) {
            $sourceSetRecords[] = implode("\t", [$record['path'], (string) $record['bytes'], $record['sha256']]);
            $sourceBytes += $record['bytes'];
        }
        $sourceSetSha256 = hash('sha256', implode("\n", $sourceSetRecords));
        $builderImplementationSha256 = MappingImplementation::builderSha256();
        $data = [
            'schema' => 'kongono.data-mapping-artifact',
            'version' => 1,
            'source' => [
                'path' => $sourceSnapshot['path'],
                'bytes' => $sourceSnapshot['size'],
                'sha256' => $sourceSnapshot['sha256'],
            ],
            'project' => [
                'composer_json_sha256' => $composerSnapshot['sha256'],
                'composer_lock_sha256' => $lockSnapshot['sha256'],
            ],
            'builder' => [
                'package' => 'kongono/database-mapping',
                'contract_version' => self::BUILDER_CONTRACT_VERSION,
                'implementation_sha256' => $builderImplementationSha256,
            ],
            'declaration_state' => [
                'source_set_sha256' => $sourceSetSha256,
                'source_count' => count($compiled['sources']),
                'source_bytes' => $sourceBytes,
                'sources' => $compiled['sources'],
            ],
            'model_sha256' => $modelSha256,
            'model' => $compiled['model'],
        ];
        $artifactBytes = CanonicalJson::encode($data);
        if (strlen($artifactBytes) > self::MAX_ARTIFACT_BYTES) {
            throw InvalidMappingSourceException::invalid(MappingFailureCategory::MappingBounds);
        }

        PathGuard::reverify($sourceSnapshot);
        PathGuard::reverify($composerSnapshot);
        PathGuard::reverify($lockSnapshot);
        foreach ($compiled['snapshots'] as $snapshot) {
            PathGuard::reverify($snapshot);
        }

        return new MappingArtifact(
            $artifactBytes,
            hash('sha256', $artifactBytes),
            $sourceSnapshot['sha256'],
            $modelSha256,
            $sourceSetSha256,
            $builderImplementationSha256,
        );
    }
}
