<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Vocabulary;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Defines the closed version-1 structural Composition node vocabulary. */
#[Api(ApiStatus::Experimental)]
enum NodeType: string
{
    case CompositionInput = 'composition.input';
    case CompositionService = 'composition.service';
    case CompositionContract = 'composition.contract';
    case CompositionRoot = 'composition.root';
}
