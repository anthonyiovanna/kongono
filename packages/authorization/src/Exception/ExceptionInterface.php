<?php

declare(strict_types=1);

namespace Kongono\Authorization\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks exceptions owned by the Authorization package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
