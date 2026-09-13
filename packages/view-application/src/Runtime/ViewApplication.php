<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Exception\ViewApplicationOperationException;
use Kongono\ViewApplication\Profile\ViewApplicationProfile;
use Kongono\ViewContract\Failure\RenderFailure;
use Kongono\ViewHttp\Http\CompletedViewResponseAdapter;
use Kongono\ViewRuntime\Artifact\LoadedCompiledViewArtifact;
use Kongono\ViewRuntime\Render\CompiledViewRenderer;
use Psr\Http\Message\ResponseInterface;

/** Runs isolated synchronous render and response operations over one loaded profile. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewApplication
{
    public function __construct(
        private ViewApplicationProfile $profile,
        private LoadedCompiledViewArtifact $artifact,
        private CompiledViewRenderer $renderer,
        private CompletedViewResponseAdapter $responseAdapter,
    ) {}

    public function profile(): ViewApplicationProfile
    {
        return $this->profile;
    }

    public function run(ViewApplicationRunInput $input): ResponseInterface|RenderFailure
    {
        if (!$input->request()->compiledViewIdentity()->equals($this->artifact->compiledViewIdentity())) {
            throw ViewApplicationOperationException::mismatch();
        }
        $rendered = $this->renderer->render($this->artifact, $input->request());
        if ($rendered instanceof RenderFailure) {
            return $rendered;
        }

        return $this->responseAdapter->adapt($rendered, $input->responsePolicy());
    }
}
