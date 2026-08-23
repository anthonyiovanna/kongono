<?php

declare(strict_types=1);

namespace Kongono\Chie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports that the request base identity differs from the current artifact source. */
#[Api(ApiStatus::Experimental)]
final class ProposalTargetStaleException extends ChieException {}
