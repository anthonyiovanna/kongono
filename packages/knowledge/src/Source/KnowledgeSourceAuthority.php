<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Source;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the semantic authority class carried by one exact source. */
#[Api(ApiStatus::Experimental)]
enum KnowledgeSourceAuthority: string
{
    case GoverningAuthority = 'governing-authority';
    case ImplementationEvidence = 'implementation-evidence';
    case CurrentExplanatoryDocumentation = 'current-explanatory-documentation';
    case HistoricalEvidence = 'historical-evidence';
    case WorkspaceObservation = 'workspace-observation';
    case DerivedOrAdvisoryMaterial = 'derived-or-advisory-material';
}
