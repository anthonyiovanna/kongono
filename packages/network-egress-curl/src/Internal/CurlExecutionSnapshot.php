<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;

/** Retains one terminal mapped emission and bounded numeric native status only. */
#[Api(ApiStatus::Internal)]
final readonly class CurlExecutionSnapshot
{
    public function __construct(
        private EgressTransportEmission $emission,
        private int $curlCode,
    ) {
        if ($curlCode < 0 || $curlCode > 999 || !$emission->terminal()) {
            throw InvalidCurlTransportProfileException::request('response_shape');
        }
    }

    public function emission(): EgressTransportEmission
    {
        return $this->emission;
    }

    public function curlCode(): int
    {
        return $this->curlCode;
    }
}
