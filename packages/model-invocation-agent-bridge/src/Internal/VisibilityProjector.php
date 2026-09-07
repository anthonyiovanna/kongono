<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Internal;

use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;

/** Projects visibility only through the fixed same-name non-widening map. */
#[Api(ApiStatus::Internal)]
final class VisibilityProjector
{
    public static function project(ModelVisibility $visibility): AgentVisibility
    {
        return match ($visibility) {
            ModelVisibility::Private => AgentVisibility::Private,
            ModelVisibility::Protected => AgentVisibility::Protected,
            ModelVisibility::Application => AgentVisibility::Application,
        };
    }

    private function __construct() {}
}
