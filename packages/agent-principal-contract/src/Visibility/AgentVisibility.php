<?php

declare(strict_types=1);

namespace Kongono\AgentPrincipalContract\Visibility;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Classifies intended visibility without filtering or granting access. */
#[Api(ApiStatus::Experimental)]
enum AgentVisibility: string
{
    case Private = 'private';
    case Protected = 'protected';
    case Application = 'application';
}
