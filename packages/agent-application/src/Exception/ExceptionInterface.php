<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Public catch boundary for every Agent Application package failure. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
