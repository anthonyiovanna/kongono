<?php

declare(strict_types=1);

namespace Kongono\Oshie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports an unavailable explicit artifact or required source. */
#[Api(ApiStatus::Experimental)]
final class ArtifactUnavailableException extends OshieException {}
