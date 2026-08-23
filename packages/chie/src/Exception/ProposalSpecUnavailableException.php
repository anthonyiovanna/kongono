<?php

declare(strict_types=1);

namespace Kongono\Chie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Reports that the one explicit proposal specification could not be safely read. */
#[Api(ApiStatus::Experimental)]
final class ProposalSpecUnavailableException extends ChieException {}
