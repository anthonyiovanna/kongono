<?php

declare(strict_types=1);

namespace Kongono\Oshie\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Marks exceptions owned by the Oshie package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends \Throwable {}
