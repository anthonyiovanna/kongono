<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks failures owned by the external-service inspection package. */
#[Api(ApiStatus::Experimental)]
interface ExceptionInterface extends Throwable {}
