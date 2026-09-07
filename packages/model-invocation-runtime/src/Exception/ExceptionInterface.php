<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Marker for failures owned by the model invocation runtime package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends \Throwable {}
