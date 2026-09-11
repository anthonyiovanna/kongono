<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Runtime;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Runtime\LoadedModelInvocationWiring;
use Kongono\ModelInvocationApplication\Runtime\ModelInvocationApplicationRunInput;
use Kongono\ModelInvocationApplication\Workflow\CurrentModelInvocationApplicationBuild;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationAdapter;
use Kongono\ModelProviderOpenAiResponses\Exception\InvalidOpenAiResponsesApplicationProfileException;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesApplicationLifecycleException;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesApplicationOperationException;
use Kongono\ModelProviderOpenAiResponses\Execution\OpenAiResponsesInvocationContext;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfileFactory;
use Throwable;

/** Composes one exact provider adapter with one accepted Phase 14 application. */
#[Api(ApiStatus::Experimental)]
final class OpenAiResponsesApplication
{
    private OpenAiResponsesApplicationState $state = OpenAiResponsesApplicationState::Ready;
    private ?LoadedModelInvocationWiring $loadedWiring;

    /** @internal Constructed only from one current lower application build. */
    public function __construct(
        private readonly OpenAiResponsesApplicationProfile $profile,
        CurrentModelInvocationApplicationBuild $modelBuild,
    ) {
        $current = (new OpenAiResponsesApplicationProfileFactory())->create(
            $profile->providerProfile(),
            $profile->modelInvocationProfile(),
            $profile->externalServiceProfile(),
        );
        if (!$current->identity()->equals($profile->identity())
            || !$modelBuild->profile()->identity()->equals($profile->modelInvocationProfile()->identity())
            || $modelBuild->loadedWiring()->started()
        ) {
            throw InvalidOpenAiResponsesApplicationProfileException::invalid('loaded_profile');
        }
        $this->loadedWiring = $modelBuild->loadedWiring();
    }

    public function profile(): OpenAiResponsesApplicationProfile
    {
        return $this->profile;
    }
    public function state(): OpenAiResponsesApplicationState
    {
        return $this->state;
    }
    public function hasActiveOperation(): bool
    {
        return OpenAiResponsesApplicationState::Active === $this->state;
    }

    public function run(OpenAiResponsesApplicationRunInput $input): InvocationRuntimeResult
    {
        if (OpenAiResponsesApplicationState::Active === $this->state) {
            throw OpenAiResponsesApplicationLifecycleException::failed('active');
        }
        if (OpenAiResponsesApplicationState::Retired === $this->state || !$this->loadedWiring instanceof LoadedModelInvocationWiring) {
            throw OpenAiResponsesApplicationLifecycleException::failed('retired');
        }

        $loadedWiring = $this->loadedWiring;
        $this->loadedWiring = null;
        $this->state = OpenAiResponsesApplicationState::Active;
        try {
            $current = (new OpenAiResponsesApplicationProfileFactory())->create(
                $this->profile->providerProfile(),
                $this->profile->modelInvocationProfile(),
                $this->profile->externalServiceProfile(),
            );
            if (!$current->identity()->equals($this->profile->identity())) {
                throw InvalidOpenAiResponsesApplicationProfileException::invalid('component_identity');
            }
            $factoryProfile = $input->applicationFactory()->profile();
            if (!$factoryProfile->identity()->equals($this->profile->externalServiceProfile()->identity())) {
                throw InvalidOpenAiResponsesApplicationProfileException::invalid('factory_profile');
            }

            try {
                $context = new OpenAiResponsesInvocationContext(
                    $this->profile->modelInvocationProfile()->source()->identity(),
                    $input->applicationFactory(),
                    $input->externalServiceOperationIdentity(),
                    $input->credentialPurpose(),
                    $input->credentialMaximumLifetimeMilliseconds(),
                    $input->egressReceiptIdentity(),
                    $input->egressAttemptIdentity(),
                    $input->egressAuthorityReference(),
                    $input->egressAuthorityCurrentness(),
                    $input->egressCancellation(),
                    $input->credentialClock(),
                    $input->egressClock(),
                );
                $adapter = new OpenAiResponsesInvocationAdapter($this->profile->providerProfile(), $context);
            } catch (Throwable $failure) {
                if ($failure instanceof Error) {
                    throw $failure;
                }
                throw OpenAiResponsesApplicationOperationException::failed('context', $failure);
            }

            try {
                $modelApplication = $loadedWiring->start($adapter);
            } catch (Throwable $failure) {
                if ($failure instanceof Error) {
                    throw $failure;
                }
                throw OpenAiResponsesApplicationOperationException::failed('application_start', $failure);
            }
            try {
                return $modelApplication->run(new ModelInvocationApplicationRunInput(
                    $input->invocationReceiptIdentity(),
                    $input->invocationCancellation(),
                    $input->invocationClock(),
                ));
            } catch (Throwable $failure) {
                if ($failure instanceof Error) {
                    throw $failure;
                }
                throw OpenAiResponsesApplicationOperationException::failed('invocation_run', $failure);
            }
        } finally {
            $this->state = OpenAiResponsesApplicationState::Retired;
            $this->loadedWiring = null;
            unset($loadedWiring, $current, $factoryProfile, $context, $adapter, $modelApplication);
        }
    }
}
