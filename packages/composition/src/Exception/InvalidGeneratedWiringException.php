<?php

declare(strict_types=1);

namespace Kongono\Composition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports malformed, noncanonical, stale, or tampered generated-wiring bytes. */
#[Api(ApiStatus::Public)]
final class InvalidGeneratedWiringException extends CompositionException {}
