<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Workflow;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Exception\NetworkEgressApplicationWorkflowException;
use Kongono\NetworkEgressApplication\Generated\NetworkEgressWiringGenerator;
use Kongono\NetworkEgressApplication\Generated\NetworkEgressWiringPublisher;
use Kongono\NetworkEgressApplication\Generated\NetworkEgressWiringValidator;
use Kongono\NetworkEgressApplication\Internal\PathGuard;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressWiringLoader;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;
use Throwable;

/** Builds, publishes, and loads one exact policy-to-application workflow. */
#[Api(ApiStatus::Experimental)]
final class NetworkEgressApplicationBuildWorkflow
{
    public function build(string $applicationRoot, string $relativeOutputDirectory, EgressPolicySource $source, string $artifactBytes): CurrentNetworkEgressApplicationBuild
    {
        try {
            $root = PathGuard::applicationRoot($applicationRoot);
            PathGuard::publicationLocations($root, $relativeOutputDirectory);
        } catch (Throwable $failure) {
            throw NetworkEgressApplicationWorkflowException::failed('path', $failure);
        }
        try {
            $profile = (new NetworkEgressApplicationProfileFactory())->create($source, $artifactBytes);
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw NetworkEgressApplicationWorkflowException::failed('profile', $failure);
        }
        try {
            $generated = (new NetworkEgressWiringGenerator())->generate($profile);
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw NetworkEgressApplicationWorkflowException::failed('generate', $failure);
        }
        try {
            (new NetworkEgressWiringValidator())->validateCurrent($profile, $generated->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw NetworkEgressApplicationWorkflowException::failed('validate', $failure);
        }
        try {
            $published = (new NetworkEgressWiringPublisher())->publish($root, $relativeOutputDirectory, $generated);
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw NetworkEgressApplicationWorkflowException::failed('publish', $failure);
        }
        try {
            $loaded = (new NetworkEgressWiringLoader())->load($root, $profile, $published);
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw NetworkEgressApplicationWorkflowException::failed('load', $failure);
        }

        return new CurrentNetworkEgressApplicationBuild($profile, $generated, $published, $loaded);
    }
}
