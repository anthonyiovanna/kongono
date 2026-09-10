<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks exceptions raised by the optional system-resolver adapter. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
