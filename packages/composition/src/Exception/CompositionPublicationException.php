<?php

declare(strict_types=1);

namespace Kongono\Composition\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports failure to publish one validated artifact without overwrite. */
#[Api(ApiStatus::Public)]
final class CompositionPublicationException extends CompositionException {}
