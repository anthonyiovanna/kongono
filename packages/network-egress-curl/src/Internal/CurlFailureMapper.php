<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;

/** Maps numeric native outcomes and bounded observations without foreign text. */
#[Api(ApiStatus::Internal)]
final class CurlFailureMapper
{
    public function map(int $curlCode, ?string $violation, int $requestBytes): EgressFailure
    {
        if (in_array($violation, ['header_budget', 'request_budget', 'response_budget', 'buffer_budget'], true)) {
            return new EgressFailure(EgressFailureKind::Budget, 'curl_budget_exceeded');
        }
        if (in_array($violation, ['response_header', 'response_shape', 'stream_state'], true)) {
            return new EgressFailure(EgressFailureKind::Response, 'curl_response_rejected');
        }
        if (0 === $curlCode) {
            return new EgressFailure(EgressFailureKind::Protocol, 'curl_protocol_rejected');
        }
        if (28 === $curlCode) {
            return new EgressFailure(EgressFailureKind::Timeout, 'curl_timeout');
        }
        if (in_array($curlCode, [35, 51, 53, 58, 59, 60, 64, 66, 77, 80, 82, 83, 90, 91], true)) {
            return new EgressFailure(EgressFailureKind::Tls, 'curl_tls_failed');
        }
        if (in_array($curlCode, [5, 6, 7, 45, 52, 55, 56], true)) {
            return new EgressFailure(EgressFailureKind::Connection, 'curl_connection_failed');
        }
        if (in_array($curlCode, [1, 2, 3, 4, 8, 16, 47, 61, 92], true)) {
            return new EgressFailure(EgressFailureKind::Protocol, 'curl_protocol_failed');
        }
        if ($requestBytes > 0) {
            return new EgressFailure(EgressFailureKind::UncertainEffect, 'curl_transfer_uncertain');
        }

        return new EgressFailure(EgressFailureKind::Connection, 'curl_transfer_failed');
    }
}
