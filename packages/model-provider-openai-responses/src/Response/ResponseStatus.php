<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Response;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Identifies the only terminal response states supported by mapping version 1. */
#[Api(ApiStatus::Internal)]
enum ResponseStatus: string
{
    case Completed = 'completed';
    case Incomplete = 'incomplete';
}
