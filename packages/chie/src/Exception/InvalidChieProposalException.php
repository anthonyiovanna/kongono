<?php

declare(strict_types=1);

namespace Kongono\Chie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports a bounded invalid or unsupported proposal without rejected-value disclosure. */
#[Api(ApiStatus::Experimental)]
final class InvalidChieProposalException extends ChieException {}
