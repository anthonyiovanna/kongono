<?php

declare(strict_types=1);

namespace Kongono\Kernel\Exception;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;
use RuntimeException;

/**
 * Provides the Internal runtime-exception base for deliberate Kernel failures.
 *
 * Consumers should catch {@see ExceptionInterface}; this base is not an extension
 * point and has no logging, rendering, transport, telemetry, or retry semantics.
 */
#[Api(ApiStatus::Internal)]
abstract class KernelException extends RuntimeException implements ExceptionInterface {}
