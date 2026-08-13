<?php

declare(strict_types=1);

namespace Kongono\Kernel\Exception;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;
use Throwable;

/**
 * Marks failures deliberately raised by the Kernel package.
 *
 * Consumers may catch this Public boundary without depending on an Experimental
 * concrete exception. Native engine errors are not promised to implement it.
 */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable
{
    public function diagnosticId(): string;

    public function diagnosticTitle(): string;

    public function diagnosticRemediation(): string;
}
