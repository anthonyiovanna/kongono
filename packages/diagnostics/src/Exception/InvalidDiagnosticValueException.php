<?php

declare(strict_types=1);

namespace Kongono\Diagnostics\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports a value that violates the closed Diagnostics structural contract. */
#[Api(ApiStatus::Experimental)]
final class InvalidDiagnosticValueException extends DiagnosticsException {}
