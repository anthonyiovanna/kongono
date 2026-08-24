<?php

declare(strict_types=1);

namespace Kongono\Composition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports a failed content-addressed generated-wiring publication attempt. */
#[Api(ApiStatus::Public)]
final class GeneratedWiringPublicationException extends CompositionException {}
