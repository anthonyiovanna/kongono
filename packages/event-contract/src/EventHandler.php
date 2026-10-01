<?php

declare(strict_types=1);

namespace Kongono\EventContract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Receives an application event when a separately supplied runtime invokes it. */
#[Api(ApiStatus::Experimental)]
interface EventHandler
{
    public function handle(ApplicationEvent $event): void;
}
