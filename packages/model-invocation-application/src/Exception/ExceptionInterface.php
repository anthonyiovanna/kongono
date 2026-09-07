<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks every exception owned by the Model Invocation Application package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
