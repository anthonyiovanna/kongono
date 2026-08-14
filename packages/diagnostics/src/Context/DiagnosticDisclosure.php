<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Context;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/**
 * Classifies a schema-approved diagnostic field for later disclosure policy.
 *
 * Safe is eligibility rather than automatic disclosure. Protected requires a
 * trusted boundary. Forbidden material must be excluded before construction.
 */
#[Api(ApiStatus::Public)]
enum DiagnosticDisclosure: string
{
    case Safe = 'safe';
    case Protected = 'protected';
}
