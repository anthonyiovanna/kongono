<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Exception;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

/**
 * Reports an invalid structural value or Application Graph snapshot.
 *
 * The Experimental exception identifies the violated invariant without adding
 * transport, serialization, rendering, or reporting semantics.
 */
#[Api(ApiStatus::Experimental)]
final class InvalidGraphException extends GraphException {}
