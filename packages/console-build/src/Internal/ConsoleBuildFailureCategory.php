<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Provides the closed Safe failure-category vocabulary for command builds. */
#[Api(ApiStatus::Internal)]
enum ConsoleBuildFailureCategory: string
{
    case SourcePath = 'source_path';
    case SourceBytes = 'source_bytes';
    case SourceJson = 'source_json';
    case SourceSchema = 'source_schema';
    case SourceBounds = 'source_bounds';
    case Model = 'model';
    case ArtifactBytes = 'artifact_bytes';
    case ArtifactStructure = 'artifact_structure';
    case ArtifactIdentity = 'artifact_identity';
    case CurrentState = 'current_state';
    case ChangedDuringRead = 'changed_during_read';
}
