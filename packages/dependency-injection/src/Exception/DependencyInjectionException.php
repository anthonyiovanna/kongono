<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Exception;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;
use RuntimeException;

/**
 * Provides the Internal base for deliberate dependency-resolution failures.
 *
 * The base defines no transport, logging, retry, serialization, or rendering
 * policy. Consumers should catch the package's Public marker when appropriate.
 */
#[Api(ApiStatus::Internal)]
abstract class DependencyInjectionException extends RuntimeException implements ExceptionInterface {}
