<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;

/** Maps only exact native HTTP/1.1 evidence into the neutral protocol enum. */
#[Api(ApiStatus::Internal)]
final class CurlProtocolMapper
{
    public function map(int $nativeProtocol): NegotiatedProtocol
    {
        return CURL_HTTP_VERSION_1_1 === $nativeProtocol ? NegotiatedProtocol::Http11 : NegotiatedProtocol::Unknown;
    }
}
