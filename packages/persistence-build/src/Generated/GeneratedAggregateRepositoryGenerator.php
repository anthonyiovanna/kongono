<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Internal\AggregateRepositoryGenerationImplementation;
use Kongono\PersistenceBuild\Internal\AggregateRepositorySourceCompiler;
use Kongono\PersistenceBuild\Internal\GeneratedAggregateRepositoryEmitter;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceGenerationImplementation;

/** Generates deterministic repositories from exact-current separate source evidence. */
#[Api(ApiStatus::Experimental)]
final class GeneratedAggregateRepositoryGenerator
{
    public const MAX_GENERATED_BYTES = 16_777_216;

    public function generate(string $applicationRoot, string $sourceRelativePath, string $persistenceArtifactBytes): GeneratedAggregateRepositoryArtifact
    {
        $compiled = (new AggregateRepositorySourceCompiler())->compile($applicationRoot, $sourceRelativePath, $persistenceArtifactBytes);
        $artifact = $compiled['persistence_artifact'];
        $bytes = (new GeneratedAggregateRepositoryEmitter())->emit(
            $compiled['source_path'],
            $compiled['source_sha256'],
            $artifact->artifactSha256(),
            $compiled['persistence_model_sha256'],
            PersistenceGenerationImplementation::generatorSha256(),
            AggregateRepositoryGenerationImplementation::generatorSha256(),
            $compiled['generated_namespace'],
            $compiled['repositories'],
        );
        if (strlen($bytes) > self::MAX_GENERATED_BYTES) {
            throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidGeneratedAggregateRepositories, PersistenceBuildFailureCategory::GeneratedAggregateRepositories);
        }

        return (new GeneratedAggregateRepositoryValidator())->validateStructure($bytes);
    }
}
