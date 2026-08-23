<?php

declare(strict_types=1);

namespace Kongono\Oshie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports invalid artifact, curriculum, section, citation, or response bounds. */
#[Api(ApiStatus::Experimental)]
final class InvalidArtifactException extends OshieException {}
