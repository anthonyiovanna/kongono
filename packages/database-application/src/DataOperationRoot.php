<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Marks the sole application-owned root exposed by a generated data operation. */
#[Api(ApiStatus::Experimental)]
interface DataOperationRoot {}
