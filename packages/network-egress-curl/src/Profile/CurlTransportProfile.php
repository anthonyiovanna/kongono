<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Profile;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Fixes explicit TLS trust and the separate plain-HTTP interoperability opt-in. */
#[Api(ApiStatus::Experimental)]
final readonly class CurlTransportProfile
{
    public function __construct(
        private CurlTlsTrust $tlsTrust,
        private bool $allowPlainHttp = false,
    ) {}

    public function tlsTrust(): CurlTlsTrust
    {
        return $this->tlsTrust;
    }

    public function allowsPlainHttp(): bool
    {
        return $this->allowPlainHttp;
    }
}
