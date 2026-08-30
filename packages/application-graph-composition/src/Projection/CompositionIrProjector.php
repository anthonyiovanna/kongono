<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Projection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphComposition\Internal\CompositionArtifactGuard;
use Kongono\ApplicationGraphComposition\Internal\CompositionModelProjector;
use Kongono\Composition\Build\CompositionArtifact;

/** Projects one exact in-memory Composition v2 artifact into fresh version-1 IR records. */
#[Api(ApiStatus::Experimental)]
final class CompositionIrProjector
{
    public function project(CompositionArtifact $artifact): CompositionIrProjection
    {
        $admitted = (new CompositionArtifactGuard())->admit($artifact);

        return (new CompositionModelProjector())->project($admitted['model'], $admitted['artifact_sha256']);
    }
}
