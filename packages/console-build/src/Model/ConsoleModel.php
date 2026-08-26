<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\CommandSpecificationSet;

/** Retains one detached immutable set of compiled command specifications. */
#[Api(ApiStatus::Experimental)]
final readonly class ConsoleModel
{
    public function __construct(private CommandSpecificationSet $specifications) {}

    public function specifications(): CommandSpecificationSet
    {
        return $this->specifications;
    }
}
