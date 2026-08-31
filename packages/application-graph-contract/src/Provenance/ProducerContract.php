<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Provenance;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the exact admitted producer contract without granting authority. */
#[Api(ApiStatus::Experimental)]
enum ProducerContract: string
{
    case CompositionV2 = 'kongono.composition/2';
    case ExtensionV1 = 'kongono.extension/1';
}
