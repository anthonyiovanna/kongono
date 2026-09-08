<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Transport;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Records negotiated-protocol evidence without selecting a transport. */
#[Api(ApiStatus::Experimental)]
enum NegotiatedProtocol: string
{
    case Unknown = 'unknown';
    case Http11 = 'http_1_1';
    case Http2 = 'http_2';
    case Http3 = 'http_3';
}
