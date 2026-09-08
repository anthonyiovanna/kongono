<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks failures originating from the optional cURL transport package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
