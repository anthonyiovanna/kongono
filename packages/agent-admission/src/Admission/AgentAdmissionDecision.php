<?php

declare(strict_types=1);

namespace Kongono\AgentAdmission\Admission;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies deterministic admission evidence without granting authority. */
#[Api(ApiStatus::Experimental)]
enum AgentAdmissionDecision: string
{
    case Admitted = 'admitted';
    case Denied = 'denied';
}
