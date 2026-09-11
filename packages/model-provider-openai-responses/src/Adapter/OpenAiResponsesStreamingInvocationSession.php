<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationRunInput;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplicationState;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOutcome;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterEmission;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterSession;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Execution\OpenAiResponsesInvocationContext;
use Kongono\ModelProviderOpenAiResponses\Internal\OpenAiResponsesRequestFactory;
use Kongono\ModelProviderOpenAiResponses\Internal\OpenAiResponsesResultNormalizer;
use Kongono\ModelProviderOpenAiResponses\Internal\OpenAiResponsesStreamNormalizer;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Request\EncodedResponsesRequest;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseEventDecoder;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseFrameReader;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseStateMachine;
use Throwable;

/** Owns one bounded SSE response and advances it only on consumer pulls. */
#[Api(ApiStatus::Internal)]
final class OpenAiResponsesStreamingInvocationSession implements InvocationAdapterSession
{
    private OpenAiResponsesStreamingInvocationSessionState $state = OpenAiResponsesStreamingInvocationSessionState::Ready;
    private ?InvocationPlanSource $source;
    private ?OpenAiResponsesProfile $profile;
    private ?OpenAiResponsesInvocationContext $context;
    private ?ExternalServiceApplicationProfile $applicationProfile;
    private ?EncodedResponsesRequest $encodedRequest;
    private ?ResponsesSseFrameReader $reader = null;
    private ?ResponsesSseStateMachine $machine = null;
    private ?OpenAiResponsesStreamNormalizer $normalizer = null;
    /** @var list<InvocationAdapterEmission> */
    private array $terminalQueue = [];

    public function __construct(
        InvocationPlanSource $source,
        OpenAiResponsesProfile $profile,
        OpenAiResponsesInvocationContext $context,
        ExternalServiceApplicationProfile $applicationProfile,
        EncodedResponsesRequest $encodedRequest,
    ) {
        $this->source = $source;
        $this->profile = $profile;
        $this->context = $context;
        $this->applicationProfile = $applicationProfile;
        $this->encodedRequest = $encodedRequest;
    }

    public function state(): OpenAiResponsesStreamingInvocationSessionState
    {
        return $this->state;
    }

    public function next(): ?InvocationAdapterEmission
    {
        if (OpenAiResponsesStreamingInvocationSessionState::Closed === $this->state
            || OpenAiResponsesStreamingInvocationSessionState::Exhausted === $this->state
        ) {
            return null;
        }
        if ([] !== $this->terminalQueue) {
            $emission = array_shift($this->terminalQueue);
            if ([] === $this->terminalQueue) {
                $this->state = OpenAiResponsesStreamingInvocationSessionState::Exhausted;
                $this->releaseAll();
            }

            return $emission;
        }
        if (OpenAiResponsesStreamingInvocationSessionState::Ready === $this->state) {
            $this->start();
        }
        if (OpenAiResponsesStreamingInvocationSessionState::Streaming !== $this->state) {
            throw OpenAiResponsesInvocationException::lifecycle('stream_session_pull');
        }

        try {
            while (true) {
                $reader = $this->reader;
                $machine = $this->machine;
                $normalizer = $this->normalizer;
                $source = $this->source;
                if (null === $reader || null === $machine || null === $normalizer || null === $source) {
                    throw OpenAiResponsesInvocationException::lifecycle('stream_session_state');
                }
                $frame = $reader->next();
                if (null === $frame) {
                    throw OpenAiResponsesInvocationException::streamingOrder('terminal_missing');
                }
                $event = (new ResponsesSseEventDecoder())->decode($frame);
                $action = $machine->accept($event);
                if ('started' === $action) {
                    return InvocationAdapterEmission::event($normalizer->started($source->request()));
                }
                if ('delta' === $action) {
                    return InvocationAdapterEmission::event($normalizer->delta($source->request(), $machine->deltaText($event)));
                }
                if ('failed' === $action) {
                    $reader->assertTerminalEof();
                    $this->closeReader();
                    $this->state = OpenAiResponsesStreamingInvocationSessionState::Exhausted;
                    $emission = InvocationAdapterEmission::event($normalizer->failed($source->request()));
                    $this->releaseAll();

                    return $emission;
                }
                if ('terminal' === $action) {
                    $reader->assertTerminalEof();
                    $decoded = $machine->terminalResponse();
                    if (null === $decoded) {
                        throw OpenAiResponsesInvocationException::streamingOrder('terminal_missing');
                    }
                    $this->closeReader();
                    $usage = $normalizer->usage($source->request(), $decoded);
                    if (null !== $usage) {
                        $this->terminalQueue[] = InvocationAdapterEmission::event($usage);
                    }
                    $this->terminalQueue[] = InvocationAdapterEmission::event($normalizer->completed($source->request()));
                    $this->terminalQueue[] = InvocationAdapterEmission::result(
                        (new OpenAiResponsesResultNormalizer())->normalize($source->request(), $decoded),
                    );
                    $this->state = OpenAiResponsesStreamingInvocationSessionState::Terminal;

                    return $this->next();
                }
            }
        } catch (Throwable $failure) {
            $this->state = OpenAiResponsesStreamingInvocationSessionState::Failed;
            $this->failAndRelease($failure);
        }
    }

    public function close(): void
    {
        if (OpenAiResponsesStreamingInvocationSessionState::Closed === $this->state) {
            return;
        }
        $failure = null;
        try {
            $this->closeReader();
        } catch (Throwable $caught) {
            $failure = $caught;
        } finally {
            $this->releaseAll();
            $this->state = OpenAiResponsesStreamingInvocationSessionState::Closed;
        }
        if (null !== $failure) {
            throw $failure;
        }
    }

    private function start(): void
    {
        $source = $this->source;
        $profile = $this->profile;
        $context = $this->context;
        $applicationProfile = $this->applicationProfile;
        $encoded = $this->encodedRequest;
        if (null === $source || null === $profile || null === $context || null === $applicationProfile || null === $encoded) {
            throw OpenAiResponsesInvocationException::lifecycle('stream_session_state');
        }
        $this->state = OpenAiResponsesStreamingInvocationSessionState::Active;
        try {
            try {
                $application = $context->applicationFactory()->create();
            } catch (Throwable $failure) {
                throw OpenAiResponsesInvocationException::externalService('application_creation', $failure);
            }
            if ($application->profile() !== $applicationProfile
                || ExternalServiceApplicationState::Ready !== $application->state()
                || $application->hasActiveOperation()
            ) {
                throw OpenAiResponsesInvocationException::invalidContext('application_state');
            }
            $requestFactory = new OpenAiResponsesRequestFactory($encoded, $context->operationIdentity());
            $input = new ExternalServiceApplicationRunInput(
                $context->operationIdentity(),
                $context->credentialPurpose(),
                $context->credentialMaximumLifetimeMilliseconds(),
                $requestFactory,
                $context->receiptIdentity(),
                $context->attemptIdentity(),
                $context->authorityReference(),
                $context->authorityCurrentness(),
                $context->cancellation(),
                $context->credentialClock(),
                $context->egressClock(),
            );
            try {
                $applicationResult = $application->run($input);
            } catch (Throwable $failure) {
                throw OpenAiResponsesInvocationException::externalService('application_run', $failure);
            }
            if (ExternalServiceOutcome::Succeeded !== $applicationResult->operationResult()->outcome()) {
                throw OpenAiResponsesInvocationException::externalService('operation_outcome');
            }
            $response = $applicationResult->egressResult()->operationResult()->response();
            if (null === $response) {
                throw OpenAiResponsesInvocationException::externalService('response_missing');
            }
            $this->reader = new ResponsesSseFrameReader($response);
            $this->machine = new ResponsesSseStateMachine($source->request(), $profile);
            $this->normalizer = new OpenAiResponsesStreamNormalizer();
            $this->context = null;
            $this->applicationProfile = null;
            $this->encodedRequest = null;
            $this->state = OpenAiResponsesStreamingInvocationSessionState::Streaming;
        } catch (Throwable $failure) {
            $this->state = OpenAiResponsesStreamingInvocationSessionState::Failed;
            $this->failAndRelease($failure);
        } finally {
            unset($application, $requestFactory, $input, $applicationResult, $response, $encoded, $context, $applicationProfile);
        }
    }

    private function closeReader(): void
    {
        $reader = $this->reader;
        $this->reader = null;
        if (null !== $reader) {
            $reader->close();
        }
    }

    private function failAndRelease(Throwable $primary): never
    {
        $cleanup = null;
        try {
            $this->closeReader();
        } catch (Throwable $failure) {
            $cleanup = $failure instanceof OpenAiResponsesInvocationException && null !== $failure->cleanupFailure()
                ? $failure->cleanupFailure()
                : $failure;
        } finally {
            $this->releaseAll();
        }
        if (null !== $cleanup) {
            throw OpenAiResponsesInvocationException::streamingCleanup($primary, $cleanup);
        }
        throw $primary;
    }

    private function releaseAll(): void
    {
        $this->source = null;
        $this->profile = null;
        $this->context = null;
        $this->applicationProfile = null;
        $this->encodedRequest = null;
        $this->reader = null;
        $this->machine = null;
        $this->normalizer = null;
        $this->terminalQueue = [];
    }
}
