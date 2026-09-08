<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Origin;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Restricts origin evidence to the two supported HTTP schemes. */
#[Api(ApiStatus::Experimental)]
enum OriginScheme: string
{
    case Http = 'http';
    case Https = 'https';
}
