<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Marks typed command-owned neutral data without defining presentation or serialization. */
#[Api(ApiStatus::Experimental)]
interface CommandResultData {}
