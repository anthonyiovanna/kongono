<?php

declare(strict_types=1);

namespace Kongono\Composition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports use of a retired, closed, or currently unavailable composition boundary. */
#[Api(ApiStatus::Public)]
final class RetiredCompositionBoundaryException extends CompositionException {}
