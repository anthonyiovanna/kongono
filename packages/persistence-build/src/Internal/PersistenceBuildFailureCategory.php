<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Provides the closed Safe failure-category vocabulary for persistence builds. */
#[Api(ApiStatus::Internal)]
enum PersistenceBuildFailureCategory: string
{
    case SourcePath = 'source_path';
    case SourceBytes = 'source_bytes';
    case SourceJson = 'source_json';
    case SourceSchema = 'source_schema';
    case SourceBounds = 'source_bounds';
    case MappingEvidence = 'mapping_evidence';
    case Construction = 'construction';
    case Field = 'field';
    case Aggregate = 'aggregate';
    case Relationship = 'relationship';
    case ArtifactBytes = 'artifact_bytes';
    case ArtifactStructure = 'artifact_structure';
    case ArtifactIdentity = 'artifact_identity';
    case CurrentState = 'current_state';
    case Publication = 'publication';
    case ChangedDuringRead = 'changed_during_read';
    case GenerationInput = 'generation_input';
    case GeneratedBytes = 'generated_bytes';
    case GeneratedStructure = 'generated_structure';
    case GeneratedCurrent = 'generated_current';
    case GeneratedPublication = 'generated_publication';
    case GeneratedValue = 'generated_value';
    case GeneratedCleanup = 'generated_cleanup';
    case AggregateRepositorySource = 'aggregate_repository_source';
    case GeneratedAggregateRepositories = 'generated_aggregate_repositories';
    case GeneratedAggregateCurrent = 'generated_aggregate_current';
    case AggregateRepositoryPublication = 'aggregate_repository_publication';
    case GeneratedPersistenceLoad = 'generated_persistence_load';
}
