<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Developer;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the sole package family that owns a projected diagnostic. */
#[Api(ApiStatus::Experimental)]
enum DeveloperDiagnosticOwner: string
{
    case Kernel = 'kernel';
    case ApplicationGraph = 'application_graph';
    case DependencyInjection = 'dependency_injection';
}
