<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks failures owned by the compiled-view inspection package. */
#[Api(ApiStatus::Experimental)]
interface ExceptionInterface extends Throwable {}
