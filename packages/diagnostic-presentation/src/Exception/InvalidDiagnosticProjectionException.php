<?php

declare(strict_types=1);

namespace Kongono\DiagnosticPresentation\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports structure that cannot form an exact trusted developer projection. */
#[Api(ApiStatus::Experimental)]
final class InvalidDiagnosticProjectionException extends DiagnosticPresentationException {}
