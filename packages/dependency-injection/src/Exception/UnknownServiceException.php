<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Exception;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

/**
 * Reports a requested contract that cannot be resolved by one resolver.
 *
 * This failure covers invalid, noncanonical, non-interface, and undefined
 * requested contracts; it does not wrap external autoloader failures.
 */
#[Api(ApiStatus::Experimental)]
final class UnknownServiceException extends DependencyInjectionException {}
