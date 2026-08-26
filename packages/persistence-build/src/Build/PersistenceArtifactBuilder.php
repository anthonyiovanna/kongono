<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Build;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Build\MappingArtifactBuilder;
use Kongono\DatabaseMapping\Exception\ExceptionInterface as MappingExceptionInterface;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Internal\CanonicalJson;
use Kongono\PersistenceBuild\Internal\PathGuard;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceBuildImplementation;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;

/** Builds canonical persistence evidence from explicit current application inputs. */
#[Api(ApiStatus::Experimental)]
final class PersistenceArtifactBuilder
{
    public const MAX_ARTIFACT_BYTES = 16_777_216;
    public const BUILDER_CONTRACT_VERSION = 1;

    public function build(string $applicationRoot, string $sourceRelativePath): PersistenceArtifact
    {
        $root = PathGuard::resolveApplicationRoot($applicationRoot);
        $sourceSnapshot = PathGuard::readPersistenceSource($root, $sourceRelativePath);
        PathGuard::validateSourceText($sourceSnapshot['bytes']);
        try {
            $source = CanonicalJson::decodeObject($sourceSnapshot['bytes'], 32);
        } catch (JsonException) {
            throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidSource, PersistenceBuildFailureCategory::SourceJson);
        }
        $mappingPath = $source['mapping_source'] ?? null;
        if (!is_string($mappingPath) || PathGuard::MAPPING_SOURCE_BASENAME !== basename($mappingPath)) {
            throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidMappingEvidence, PersistenceBuildFailureCategory::MappingEvidence);
        }
        $mappingSnapshot = PathGuard::readMappingSource($root, $mappingPath);
        try {
            $mappingArtifact = (new MappingArtifactBuilder())->build($root, $mappingPath);
        } catch (MappingExceptionInterface) {
            throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidMappingEvidence, PersistenceBuildFailureCategory::MappingEvidence);
        }
        try {
            $mappingData = CanonicalJson::decodeObject($mappingArtifact->canonicalBytes(), 64);
        } catch (JsonException) {
            throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidMappingEvidence, PersistenceBuildFailureCategory::MappingEvidence);
        }
        $compiled = (new PersistenceModelCompiler())->compile($root, $source, $mappingData);
        $modelBytes = CanonicalJson::encode($compiled['model_data']);
        $modelSha256 = hash('sha256', $modelBytes);
        $mappingModel = $mappingData['model'] ?? null;
        if (!is_array($mappingModel) || array_is_list($mappingModel)) {
            throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidMappingEvidence, PersistenceBuildFailureCategory::MappingEvidence);
        }
        /** @var array<string, mixed> $mappingModel */
        $mappingModelBytes = CanonicalJson::encode($mappingModel);
        $composerSnapshot = PathGuard::readProjectFile($root, 'composer.json');
        $lockSnapshot = PathGuard::readProjectFile($root, 'composer.lock');
        $implementationSha256 = PersistenceBuildImplementation::builderSha256();
        $domainSources = [];
        foreach ($compiled['model']->entities() as $entity) {
            $domainSources[] = [
                'entity' => $entity->entity(),
                'path' => $entity->sourcePath(),
                'bytes' => $entity->sourceBytes(),
                'sha256' => $entity->sourceSha256(),
            ];
        }
        $artifactData = [
            'schema' => 'kongono.persistence-artifact',
            'version' => 1,
            'model_schema' => 'kongono.persistence-model',
            'model_version' => 1,
            'source' => [
                'path' => $sourceSnapshot['path'],
                'bytes' => $sourceSnapshot['size'],
                'sha256' => $sourceSnapshot['sha256'],
            ],
            'provenance' => [
                'composer_json' => ['bytes' => $composerSnapshot['size'], 'sha256' => $composerSnapshot['sha256']],
                'composer_lock' => ['bytes' => $lockSnapshot['size'], 'sha256' => $lockSnapshot['sha256']],
                'persistence_source' => ['path' => $sourceSnapshot['path'], 'bytes' => $sourceSnapshot['size'], 'sha256' => $sourceSnapshot['sha256']],
                'mapping_source' => ['path' => $mappingSnapshot['path'], 'bytes' => $mappingSnapshot['size'], 'sha256' => $mappingSnapshot['sha256']],
                'mapping_artifact' => [
                    'bytes' => strlen($mappingArtifact->canonicalBytes()),
                    'sha256' => $mappingArtifact->artifactSha256(),
                    'builder_implementation_sha256' => $mappingArtifact->builderImplementationSha256(),
                ],
                'mapping_model' => ['bytes' => strlen($mappingModelBytes), 'sha256' => hash('sha256', $mappingModelBytes)],
                'persistence_builder' => [
                    'package' => 'kongono/persistence-build',
                    'contract_version' => self::BUILDER_CONTRACT_VERSION,
                    'implementation_sha256' => $implementationSha256,
                ],
                'domain_sources' => $domainSources,
                'generated_namespace' => $compiled['model']->generatedNamespace(),
                'persistence_model' => ['bytes' => strlen($modelBytes), 'sha256' => $modelSha256],
            ],
            'model' => $compiled['model_data'],
        ];
        $artifactBytes = CanonicalJson::encode($artifactData);
        if (strlen($artifactBytes) > self::MAX_ARTIFACT_BYTES) {
            throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidSource, PersistenceBuildFailureCategory::SourceBounds);
        }

        PathGuard::reverify($sourceSnapshot);
        PathGuard::reverify($mappingSnapshot);
        PathGuard::reverify($composerSnapshot);
        PathGuard::reverify($lockSnapshot);
        foreach ($compiled['snapshots'] as $snapshot) {
            PathGuard::reverify($snapshot);
        }

        return new PersistenceArtifact(
            $artifactBytes,
            hash('sha256', $artifactBytes),
            $sourceSnapshot['sha256'],
            $modelSha256,
            $mappingArtifact->artifactSha256(),
            $implementationSha256,
            $compiled['model'],
        );
    }
}
