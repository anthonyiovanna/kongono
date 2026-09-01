<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks every public failure emitted by the Agent Inspection package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
