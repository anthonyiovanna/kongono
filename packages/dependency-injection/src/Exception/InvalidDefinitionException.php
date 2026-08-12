<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Exception;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

/**
 * Reports an invalid service definition, definition list, or definition graph.
 *
 * Validation completes before a definition or resolver becomes usable. Exact
 * message prose is diagnostic rather than a Stable compatibility contract.
 */
#[Api(ApiStatus::Experimental)]
final class InvalidDefinitionException extends DependencyInjectionException {}
