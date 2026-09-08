<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Declares inert transport-security posture without negotiating TLS. */
#[Api(ApiStatus::Experimental)]
enum TransportSecurity: string
{
    case HttpsOnly = 'https_only';
    case ExplicitHttpAllowed = 'explicit_http_allowed';
}
