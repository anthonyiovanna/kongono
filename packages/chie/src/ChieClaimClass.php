<?php

declare(strict_types=1);

namespace Kongono\Chie;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies the semantic basis of one read-only Chie claim. */
#[Api(ApiStatus::Experimental)]
enum ChieClaimClass: string
{
    case AcceptedFrameworkFact = 'accepted-framework-fact';
    case CurrentWorkspaceObservation = 'current-workspace-observation';
    case Inference = 'inference';
    case Recommendation = 'recommendation';
    case GeneratedCandidate = 'generated-candidate';
    case UnresolvedUncertainty = 'unresolved-uncertainty';
}
