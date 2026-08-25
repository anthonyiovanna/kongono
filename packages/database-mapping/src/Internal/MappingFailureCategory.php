<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Provides the closed Safe failure-category vocabulary used by mapping diagnostics. */
#[Api(ApiStatus::Internal)]
enum MappingFailureCategory: string
{
    case SourcePath = 'source_path';
    case SourceBytes = 'source_bytes';
    case SourceJson = 'source_json';
    case SourceSchema = 'source_schema';
    case MappingBounds = 'mapping_bounds';
    case MappingName = 'mapping_name';
    case MappingDuplicate = 'mapping_duplicate';
    case RecordContract = 'record_contract';
    case Provenance = 'provenance';
    case ArtifactBytes = 'artifact_bytes';
    case ArtifactStructure = 'artifact_structure';
    case ArtifactIdentity = 'artifact_identity';
    case CurrentState = 'current_state';
    case GeneratedBytes = 'generated_bytes';
    case GeneratedStructure = 'generated_structure';
    case GeneratedCurrent = 'generated_current';
    case GeneratedLoad = 'generated_load';
    case Publication = 'publication';
    case HydrationShape = 'hydration_shape';
    case HydrationKind = 'hydration_kind';
    case HydrationValue = 'hydration_value';
    case Cardinality = 'cardinality';
    case ResultShape = 'result_shape';
    case Cleanup = 'cleanup';
}
