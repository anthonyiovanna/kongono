<?php

declare(strict_types=1);

namespace Kongono\Composition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports a rejected application root, source, declaration, graph, or provenance state. */
#[Api(ApiStatus::Public)]
final class InvalidCompositionSourceException extends CompositionException {}
