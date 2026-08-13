<?php

declare(strict_types=1);

namespace Kongono\DependencyInjection\Exception;

use Kongono\Kernel\Api\Api;
use Kongono\Kernel\Api\ApiStatus;
use Throwable;

/**
 * Marks failures deliberately raised by the dependency-injection package.
 *
 * Consumers may catch this Public boundary without depending on Experimental
 * concrete failures. Native autoload, engine, and consumer-constructor failures
 * are not wrapped merely to enter this family.
 */
#[Api(ApiStatus::Public)]
interface ExceptionInterface extends Throwable
{
    public function diagnosticId(): string;

    public function diagnosticTitle(): string;

    public function diagnosticRemediation(): string;
}
