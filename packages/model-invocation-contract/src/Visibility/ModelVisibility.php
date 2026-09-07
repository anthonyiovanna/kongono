<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Visibility;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies intended handling without granting access. */
#[Api(ApiStatus::Experimental)]
enum ModelVisibility: string
{
    case Private = 'private';
    case Protected = 'protected';
    case Application = 'application';
}
