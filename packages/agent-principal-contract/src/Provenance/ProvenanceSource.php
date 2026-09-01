<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Provenance;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies bounded source evidence without elevating it to truth or authority. */
#[Api(ApiStatus::Experimental)]
enum ProvenanceSource: string
{
    case Caller = 'caller';
    case Application = 'application';
    case Model = 'model';
    case Retrieval = 'retrieval';
    case Capability = 'capability';
    case Deterministic = 'deterministic';
}
