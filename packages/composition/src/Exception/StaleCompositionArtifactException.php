<?php

declare(strict_types=1);

namespace Kongono\Composition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports an artifact that is structurally valid but not current. */
#[Api(ApiStatus::Public)]
final class StaleCompositionArtifactException extends CompositionException {}
