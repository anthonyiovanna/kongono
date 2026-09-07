<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Runtime;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Exception\InvalidModelInvocationApplicationProfileException;
use Kongono\ModelInvocationApplication\Exception\ModelInvocationApplicationLifecycleException;
use Kongono\ModelInvocationApplication\Exception\ModelInvocationApplicationOperationException;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapter;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeRequest;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult;
use Kongono\ModelInvocationRuntime\Runtime\ModelInvocationRuntime;
use Throwable;

/** Owns one exact profile and direct adapter for one terminal synchronous run. */
#[Api(ApiStatus::Experimental)]
final class ModelInvocationApplication
{
    private ModelInvocationApplicationState $state = ModelInvocationApplicationState::Ready;
    private ?InvocationAdapter $adapter;

    /** @internal Constructed only through exact generated wiring. */
    public function __construct(private readonly ModelInvocationApplicationProfile $profile, InvocationAdapter $adapter)
    {
        try {
            if (!$profile->source()->adapterIdentity()->equals($adapter->identity())) {
                throw InvalidModelInvocationApplicationProfileException::invalid('adapter_identity_mismatch');
            }
        } catch (InvalidModelInvocationApplicationProfileException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw ModelInvocationApplicationLifecycleException::failed('adapter_identity', $failure);
        }
        $this->adapter = $adapter;
    }

    public function profile(): ModelInvocationApplicationProfile
    {
        return $this->profile;
    }
    public function state(): ModelInvocationApplicationState
    {
        return $this->state;
    }
    public function hasActiveOperation(): bool
    {
        return ModelInvocationApplicationState::Active === $this->state;
    }

    public function run(ModelInvocationApplicationRunInput $input): InvocationRuntimeResult
    {
        if (ModelInvocationApplicationState::Active === $this->state) {
            throw ModelInvocationApplicationLifecycleException::failed('operation_active');
        }
        if (ModelInvocationApplicationState::Retired === $this->state || !$this->adapter instanceof InvocationAdapter) {
            throw ModelInvocationApplicationLifecycleException::failed('application_retired');
        }

        $adapter = $this->adapter;
        $this->adapter = null;
        $this->state = ModelInvocationApplicationState::Active;
        try {
            try {
                $request = new InvocationRuntimeRequest(
                    $this->profile->source(),
                    $this->profile->artifact()->canonicalBytes(),
                    $input->receiptIdentity(),
                    $adapter,
                    $input->cancellation(),
                    $input->clock(),
                );
            } catch (Throwable $failure) {
                if ($failure instanceof \Error) {
                    throw $failure;
                }
                throw ModelInvocationApplicationOperationException::failed('request', $failure);
            }

            return (new ModelInvocationRuntime())->run($request);
        } finally {
            $this->state = ModelInvocationApplicationState::Retired;
            unset($request, $adapter);
        }
    }
}
