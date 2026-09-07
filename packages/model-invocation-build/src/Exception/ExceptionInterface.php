<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks all package-owned model-invocation build failures. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
