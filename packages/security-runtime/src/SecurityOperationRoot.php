<?php

declare(strict_types=1);

namespace Kongono\SecurityRuntime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Marks the sole application-owned root exposed by a generated security operation. */
#[Api(ApiStatus::Experimental)]
interface SecurityOperationRoot {}
