<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Stringable;
use Throwable;

/** Marks every bounded failure owned by the View Application package. */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable, Stringable
{
    public function diagnosticId(): string;
}
