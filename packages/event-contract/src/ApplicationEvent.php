<?php

declare(strict_types=1);

namespace Kongono\EventContract;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Marks an application-owned occurrence; it grants no effect authority. */
#[Api(ApiStatus::Experimental)]
interface ApplicationEvent {}
