<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Outcome;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies only terminal operation evidence, never credential or network authority. */
#[Api(ApiStatus::Experimental)]
enum CredentialLeaseRuntimeOutcome: string
{
    case Succeeded = 'succeeded';
    case FailedBeforeAcquisition = 'failed_before_acquisition';
    case FailedBeforeMaterial = 'failed_before_material';
    case MaterialUseUncertain = 'material_use_uncertain';
    case RetirementUncertain = 'retirement_uncertain';
}
