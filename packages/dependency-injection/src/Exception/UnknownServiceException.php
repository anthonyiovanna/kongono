<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/**
 * Reports a requested contract that cannot be resolved by one resolver.
 *
 * This failure covers invalid, noncanonical, non-interface, and undefined
 * requested contracts; it does not wrap external autoloader failures.
 */
#[Api(ApiStatus::Experimental)]
final class UnknownServiceException extends DependencyInjectionException {}
