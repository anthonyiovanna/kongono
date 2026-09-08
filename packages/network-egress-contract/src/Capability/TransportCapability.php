<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Capability;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Names declared transport capability evidence without discovery or selection. */
#[Api(ApiStatus::Experimental)]
enum TransportCapability: string
{
    case Http11 = 'http_1_1';
    case Http2 = 'http_2';
    case Http3 = 'http_3';
    case Tls = 'tls';
    case RequestStreaming = 'request_streaming';
    case ResponseStreaming = 'response_streaming';
    case Cancellation = 'cancellation';
    case ConnectionReuse = 'connection_reuse';
    case Proxy = 'proxy';
    case MutualTls = 'mutual_tls';
    case Trailers = 'trailers';
}
