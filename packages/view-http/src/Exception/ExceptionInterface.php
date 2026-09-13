<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Marks all failures owned by the view HTTP adaptation boundary. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable {}
