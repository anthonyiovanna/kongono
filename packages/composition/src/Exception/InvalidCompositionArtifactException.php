<?php

declare(strict_types=1);

namespace Kongono\Composition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports malformed, noncanonical, partial, or tampered artifact bytes. */
#[Api(ApiStatus::Public)]
final class InvalidCompositionArtifactException extends CompositionException {}
