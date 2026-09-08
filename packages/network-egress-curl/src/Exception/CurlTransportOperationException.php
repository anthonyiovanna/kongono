<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressCurl\Diagnostic\NetworkEgressCurlDiagnostic;
use Throwable;

/** Preserves an unexpected native failure behind bounded deterministic evidence. */
#[Api(ApiStatus::Experimental)]
final class CurlTransportOperationException extends NetworkEgressCurlException
{
    public static function transfer(EgressFailureKind $kind, int $curlCode, Throwable $previous): self
    {
        return new self(NetworkEgressCurlDiagnostic::CurlTransferFailed, [
            'stage' => 'transfer',
            'violation' => 'transfer_failed',
            'failure_kind' => $kind->value,
            'curl_code' => $curlCode,
        ], $previous);
    }

    public static function response(Throwable $previous): self
    {
        return new self(NetworkEgressCurlDiagnostic::ResponseRejected, [
            'stage' => 'response',
            'violation' => 'response_shape',
            'failure_kind' => EgressFailureKind::Response->value,
        ], $previous);
    }

    public static function cleanup(Throwable $previous): self
    {
        return new self(NetworkEgressCurlDiagnostic::SessionCleanupFailed, [
            'stage' => 'cleanup',
            'violation' => 'cleanup_failed',
            'failure_kind' => EgressFailureKind::Cleanup->value,
        ], $previous);
    }
}
