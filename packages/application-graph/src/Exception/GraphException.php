<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraph\Exception;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;
use RuntimeException;

/**
 * Provides the Internal base for deliberate Application Graph failures.
 *
 * Consumers should catch {@see ExceptionInterface}. This class defines no
 * logging, transport, localization, telemetry, or retry policy.
 */
#[Api(ApiStatus::Internal)]
abstract class GraphException extends RuntimeException implements ExceptionInterface {}
