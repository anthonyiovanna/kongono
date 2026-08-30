<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Build;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use Kongono\ApplicationGraphBuild\Graph\AuthoritativeApplicationGraph;
use Kongono\ApplicationGraphComposition\Projection\CompositionIrProjector;
use Kongono\Composition\Build\CompositionArtifact;

/** Performs one supported projection followed by one existing Graph construction call. */
#[Api(ApiStatus::Experimental)]
final class CompositionApplicationGraphBuilder
{
    public function build(CompositionArtifact $artifact): AuthoritativeApplicationGraph
    {
        $projection = (new CompositionIrProjector())->project($artifact);

        return (new ApplicationGraphBuilder())->build($projection->nodeRecords(), $projection->relationRecords());
    }
}
