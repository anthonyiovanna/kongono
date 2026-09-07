<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks failures owned by the model-invocation inspection package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
