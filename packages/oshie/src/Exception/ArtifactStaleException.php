<?php

declare(strict_types=1);

namespace Kongono\Oshie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports that artifact source identities no longer match the explicit root. */
#[Api(ApiStatus::Experimental)]
final class ArtifactStaleException extends OshieException {}
