<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Render;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewContract\Failure\RenderFailure;
use Kongono\ViewContract\Render\RenderRequest;
use Kongono\ViewContract\Render\RenderResult;
use Kongono\ViewRuntime\Artifact\LoadedCompiledViewArtifact;
use Kongono\ViewRuntime\Internal\RenderOperation;

/** Starts one isolated synchronous render for an authenticated loaded artifact. */
#[Api(ApiStatus::Experimental)]
final class CompiledViewRenderer
{
    public function render(LoadedCompiledViewArtifact $artifact, RenderRequest $request): RenderResult|RenderFailure
    {
        return (new RenderOperation())->run($artifact, $request);
    }
}
