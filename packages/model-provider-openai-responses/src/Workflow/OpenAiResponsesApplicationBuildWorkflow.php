<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Workflow;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ModelInvocationApplication\Workflow\ModelInvocationApplicationBuildWorkflow;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesApplicationWorkflowException;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfileFactory;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Runtime\OpenAiResponsesApplication;
use Throwable;

/** Delegates exact build/check ownership to the accepted Phase 14 workflow. */
#[Api(ApiStatus::Experimental)]
final class OpenAiResponsesApplicationBuildWorkflow
{
    public function build(
        string $applicationRoot,
        string $artifactFile,
        InvocationPlanSource $source,
        string $outputDirectory,
        OpenAiResponsesProfile $providerProfile,
        ExternalServiceApplicationProfile $externalProfile,
    ): CurrentOpenAiResponsesApplicationBuild {
        return $this->execute(true, $applicationRoot, $artifactFile, $source, $outputDirectory, $providerProfile, $externalProfile);
    }

    public function check(
        string $applicationRoot,
        string $artifactFile,
        InvocationPlanSource $source,
        string $outputDirectory,
        OpenAiResponsesProfile $providerProfile,
        ExternalServiceApplicationProfile $externalProfile,
    ): CurrentOpenAiResponsesApplicationBuild {
        return $this->execute(false, $applicationRoot, $artifactFile, $source, $outputDirectory, $providerProfile, $externalProfile);
    }

    private function execute(
        bool $publish,
        string $applicationRoot,
        string $artifactFile,
        InvocationPlanSource $source,
        string $outputDirectory,
        OpenAiResponsesProfile $providerProfile,
        ExternalServiceApplicationProfile $externalProfile,
    ): CurrentOpenAiResponsesApplicationBuild {
        try {
            $workflow = new ModelInvocationApplicationBuildWorkflow();
            $lower = $publish
                ? $workflow->build($applicationRoot, $artifactFile, $source, $outputDirectory)
                : $workflow->check($applicationRoot, $artifactFile, $source, $outputDirectory);
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw OpenAiResponsesApplicationWorkflowException::failed(
                $publish ? 'model_application_build' : 'model_application_check',
                $failure,
            );
        }
        try {
            $profile = (new OpenAiResponsesApplicationProfileFactory())->create($providerProfile, $lower->profile(), $externalProfile);
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw OpenAiResponsesApplicationWorkflowException::failed('application_profile', $failure);
        }
        try {
            $application = new OpenAiResponsesApplication($profile, $lower);
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw OpenAiResponsesApplicationWorkflowException::failed('application_create', $failure);
        }

        return new CurrentOpenAiResponsesApplicationBuild($profile, $lower, $application);
    }
}
