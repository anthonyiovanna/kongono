<?php

declare(strict_types=1);

namespace Kongono\ViewApplication;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Exception\ViewApplicationLoadException;
use Kongono\ViewApplication\Internal\CanonicalViewApplicationProfile;
use Kongono\ViewApplication\Profile\ViewApplicationProfile;
use Kongono\ViewApplication\Runtime\ViewApplication;
use Kongono\ViewApplication\Workflow\CurrentViewApplicationBuild;
use Kongono\ViewHttp\Http\CompletedViewResponseAdapter;
use Kongono\ViewRuntime\Artifact\CompiledViewArtifactLoader;
use Kongono\ViewRuntime\Render\CompiledViewRenderer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

/** Authenticates and loads exactly one current artifact into one reusable stateless application. */
#[Api(ApiStatus::Experimental)]
final class ViewApplicationFactory
{
    public function create(CurrentViewApplicationBuild $current, ResponseFactoryInterface $responses, StreamFactoryInterface $streams): ViewApplication
    {
        try {
            $loaded = (new CompiledViewArtifactLoader())->load(
                $current->definition()->artifactOutputRoot(),
                $current->artifactDirectory(),
                $current->compiledViewIdentity(),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ViewApplicationLoadException::failed('artifact_load', $failure);
        }
        if (!$loaded->compiledViewIdentity()->equals($current->compiledViewIdentity())
            || !hash_equals($loaded->sourceSha256(), $current->sourceIdentity()->value())
            || !hash_equals($loaded->modelSha256(), $current->modelSha256())
            || !hash_equals($loaded->generatedPhpSha256(), $current->generatedPhpSha256())
            || $loaded->viewCount() !== $current->viewCount()) {
            throw ViewApplicationLoadException::failed('profile_validation');
        }
        $profile = new ViewApplicationProfile($current, CanonicalViewApplicationProfile::identity($current));

        return new ViewApplication($profile, $loaded, new CompiledViewRenderer(), new CompletedViewResponseAdapter($responses, $streams));
    }
}
