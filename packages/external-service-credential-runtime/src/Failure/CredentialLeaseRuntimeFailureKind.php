<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Failure;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies a bounded failure without retaining collaborator data. */
#[Api(ApiStatus::Experimental)]
enum CredentialLeaseRuntimeFailureKind: string
{
    case Clock = 'clock';
    case Source = 'source';
    case LeaseProtocol = 'lease_protocol';
    case Lifetime = 'lifetime';
    case Consumer = 'consumer';
    case Retirement = 'retirement';
}
