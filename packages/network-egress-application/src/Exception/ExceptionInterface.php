<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks every exception owned by the Network Egress Application package. */
#[Api(ApiStatus::Experimental)]
interface ExceptionInterface extends Throwable {}
