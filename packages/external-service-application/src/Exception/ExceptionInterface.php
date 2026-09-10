<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks every exception owned by the External Service Application package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
