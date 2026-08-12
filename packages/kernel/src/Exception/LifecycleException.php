<?php

declare(strict_types=1);

namespace Kongono\Kernel\Exception;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;

/**
 * Reports an invalid Kernel transition or operation-ownership action.
 *
 * The failed action leaves valid lifecycle state unchanged. This Experimental type
 * carries diagnostic meaning only and defines no transport or reporting policy.
 */
#[Api(ApiStatus::Experimental)]
final class LifecycleException extends KernelException {}
