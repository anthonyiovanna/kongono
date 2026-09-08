<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Isolates one native execution so session lifecycle can be proven offline. */
#[Api(ApiStatus::Internal)]
interface CurlExecutor
{
    public function execute(): CurlExecutionSnapshot;

    public function close(): void;
}
