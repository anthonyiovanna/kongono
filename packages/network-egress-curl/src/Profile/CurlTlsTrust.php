<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;
use SensitiveParameter;

/** Stores explicit caller-owned CA certificate bytes without ambient lookup. */
#[Api(ApiStatus::Experimental)]
final readonly class CurlTlsTrust
{
    private string $caBundlePem;

    public function __construct(#[SensitiveParameter] string $caBundlePem)
    {
        $length = strlen($caBundlePem);
        if ($length < 1 || $length > 1_048_576 || str_contains($caBundlePem, "\0")) {
            throw InvalidCurlTransportProfileException::invalid('ca_bundle_bounds', $length, 1_048_576);
        }
        $begin = '-----BEGIN CERTIFICATE-----';
        $end = '-----END CERTIFICATE-----';
        $beginPosition = strpos($caBundlePem, $begin);
        $endPosition = false === $beginPosition ? false : strpos($caBundlePem, $end, $beginPosition + strlen($begin));
        if (false === $beginPosition || false === $endPosition) {
            throw InvalidCurlTransportProfileException::invalid('ca_bundle_shape');
        }
        $this->caBundlePem = substr($caBundlePem, 0);
    }

    /** Returns the exact caller-supplied CA bytes for CURLOPT_CAINFO_BLOB only. */
    public function caBundlePem(): string
    {
        return $this->caBundlePem;
    }
}
