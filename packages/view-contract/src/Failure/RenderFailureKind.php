<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Failure;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Enumerates the exact neutral render-failure kinds. */
#[Api(ApiStatus::Experimental)]
enum RenderFailureKind: string
{
    case InvalidRequest = 'invalid_request';
    case SchemaMismatch = 'schema_mismatch';
    case StaleArtifact = 'stale_artifact';
    case UnsupportedOutputContext = 'unsupported_output_context';
    case BoundExceeded = 'bound_exceeded';
    case RenderingFailed = 'rendering_failed';
    case RetirementFailed = 'retirement_failed';
}
