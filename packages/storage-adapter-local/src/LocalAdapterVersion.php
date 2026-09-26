<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the contained local record and layout version. */
#[Api(ApiStatus::Experimental)]
enum LocalAdapterVersion: int
{
    case Version1 = 1;
}
