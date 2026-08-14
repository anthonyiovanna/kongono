<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Context;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines the exact scalar kinds accepted by bounded diagnostic fields. */
#[Api(ApiStatus::Public)]
enum DiagnosticValueKind: string
{
    case Text = 'text';
    case Integer = 'integer';
    case Boolean = 'boolean';
}
