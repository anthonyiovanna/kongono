<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Tests\Support;

use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileFactory;
use Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplication;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeCredentialLease;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeCredentialLeaseSource;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeCredentialMonotonicClock;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeEgressAuthorityCurrentness;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeEgressCancellation;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeEgressTransport;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeEgressTransportSession;
use Kongono\ExternalServiceApplication\Tests\Fixture\FakeEndpointResolver;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceProfileIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Endpoint\EndpointBasePath;
use Kongono\ExternalServiceContract\Endpoint\ServiceEndpoint;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\Http\Message\Factory\StreamFactory;
use Kongono\Http\Message\Message\Response;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactBuilder;
use Kongono\ModelInvocationBuild\Budget\InvocationExecutionBudget;
use Kongono\ModelInvocationBuild\Budget\UsageLimit;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Input\InputMessage;
use Kongono\ModelInvocationContract\Input\InputRole;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Invocation\InvocationLimits;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Kongono\ModelInvocationContract\Result\ActionName;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesExternalServiceApplicationFactory;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationAdapter;
use Kongono\ModelProviderOpenAiResponses\Execution\OpenAiResponsesInvocationContext;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesFunctionDefinition;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesStructuredOutput;
use Kongono\ModelProviderOpenAiResponses\Profile\ResponseStoragePreference;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;
use Kongono\NetworkEgressApplication\Generated\GeneratedNetworkEgressApplicationBoundary;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressApplication\Runtime\LoadedNetworkEgressWiring;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplication;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactBuilder;
use Kongono\NetworkEgressPolicyBuild\Policy\AddressScope;
use Kongono\NetworkEgressPolicyBuild\Policy\CookieMode;
use Kongono\NetworkEgressPolicyBuild\Policy\CredentialMode;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicyIdentity;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;
use Kongono\NetworkEgressPolicyBuild\Policy\ProxyMode;
use Kongono\NetworkEgressPolicyBuild\Policy\RedirectMode;
use Kongono\NetworkEgressPolicyBuild\Policy\TransportSecurity;
use Kongono\NetworkEgressRuntime\Address\ResolutionEvidence;
use Kongono\NetworkEgressRuntime\Address\ResolvedAddress;
use Kongono\NetworkEgressRuntime\Clock\EgressMonotonicClock;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class OpenAiResponsesAdapterFixture implements OpenAiResponsesExternalServiceApplicationFactory
{
    public int $createCalls = 0;
    public ?ExternalServiceApplication $application = null;
    public ?FakeCredentialLease $lease = null;
    public ?FakeCredentialLeaseSource $credentialSource = null;
    public ?FakeEgressCancellation $egressCancellation = null;
    public ?FakeEndpointResolver $resolver = null;
    public ?FakeEgressTransport $transport = null;
    public ?FakeEgressTransportSession $transportSession = null;

    public readonly OpenAiResponsesProfile $openAiProfile;
    public readonly InvocationPlanSource $source;
    public readonly string $artifactBytes;
    public readonly OpenAiResponsesInvocationContext $context;

    private ExternalServiceApplicationProfile $applicationProfile;
    private TransportCapabilitySet $capabilities;
    private ResponseInterface $response;

    /**
     * @param list<InputMessage>|null $messages
     * @param list<bool>               $cancellationAnswers
     */
    public function __construct(
        ?ResponseInterface $response = null,
        ?array $messages = null,
        private readonly string $credential = 'fixture-credential-17c',
        private readonly array $cancellationAnswers = [],
        private readonly string $transportMode = 'success',
        private readonly ?Throwable $creationFailure = null,
        public readonly bool $streaming = false,
        public readonly ResultMode $resultMode = ResultMode::Text,
        private readonly string $identitySuffix = '',
        private readonly OriginScheme $endpointScheme = OriginScheme::Https,
        private readonly string $endpointBasePath = '',
    ) {
        $capabilities = match ($resultMode) {
            ResultMode::Text => $streaming
                ? [ProviderCapability::TextResult, ProviderCapability::Streaming, ProviderCapability::UsageEvidence]
                : [ProviderCapability::TextResult, ProviderCapability::UsageEvidence],
            ResultMode::Structured => [ProviderCapability::StructuredResult, ProviderCapability::UsageEvidence],
            ResultMode::ActionSuggestion => [ProviderCapability::ActionSuggestion, ProviderCapability::UsageEvidence],
        };
        $neutral = new ModelProfileReference(
            ContractVersion::Version1,
            new ProfileIdentity($this->identity('profile.openai.responses')),
            new ProviderIdentity('openai'),
            new ModelIdentity('openai-test-model'),
            new ProviderCapabilitySet($capabilities),
        );
        $schema = new StrictJsonSchema('{"additionalProperties":false,"properties":{"answer":{"type":"string"}},"required":["answer"],"type":"object"}');
        $this->openAiProfile = new OpenAiResponsesProfile(
            $neutral,
            ResultMode::Text === $resultMode ? OpenAiResponsesMappingVersion::Version1 : OpenAiResponsesMappingVersion::Version2,
            ResponseStoragePreference::Disabled,
            ResultMode::Structured === $resultMode ? new OpenAiResponsesStructuredOutput('answer_format', $schema) : null,
            ResultMode::ActionSuggestion === $resultMode ? new OpenAiResponsesFunctionDefinition(new ActionName('record_answer'), $schema) : null,
        );
        $request = new InvocationRequest(
            ContractVersion::Version1,
            new InvocationIdentity($this->identity('invocation.openai.responses')),
            $neutral,
            $messages ?? [
                new InputMessage(InputRole::System, ModelVisibility::Application, 'bounded system input'),
                new InputMessage(InputRole::User, ModelVisibility::Private, 'bounded private input'),
            ],
            $resultMode,
            $streaming,
            new InvocationLimits(4096, $streaming ? 5 : 1, 3),
        );
        $this->source = new InvocationPlanSource(
            new InvocationPlanIdentity($this->identity('plan.openai.responses')),
            new AdapterIdentity(OpenAiResponsesInvocationAdapter::IDENTITY),
            $request,
            new InvocationExecutionBudget(5000, 1, 65536, [
                new UsageLimit(new UsageMetricName('input_tokens'), new UsageUnitName('count'), 1000),
                new UsageLimit(new UsageMetricName('output_tokens'), new UsageUnitName('count'), 1000),
                new UsageLimit(new UsageMetricName('total_tokens'), new UsageUnitName('count'), 2000),
            ], null),
        );
        $this->artifactBytes = (new InvocationPlanArtifactBuilder())->build($this->source)->canonicalBytes();
        $this->buildApplicationProfile();
        $this->response = $response ?? new Response(
            200,
            ['Content-Type' => $streaming ? 'text/event-stream' : 'application/json'],
            (new StreamFactory())->createStream($streaming ? self::streamBody('completed-text-stream.sse') : self::completedBody()),
        );
        $this->egressCancellation = new FakeEgressCancellation($this->cancellationAnswers);
        $this->context = new OpenAiResponsesInvocationContext(
            $this->source->identity(),
            $this,
            new ExternalServiceOperationIdentity($this->identity('operation.openai.responses')),
            new CredentialPurpose($this->identity('invoke.openai.responses')),
            5000,
            new EgressReceiptIdentity($this->identity('receipt.openai.responses')),
            new EgressAttemptIdentity($this->identity('attempt.openai.responses')),
            new EgressAuthorityReference($this->identity('authority.openai.responses'), 1),
            new FakeEgressAuthorityCurrentness(),
            $this->egressCancellation,
            new FakeCredentialMonotonicClock([0, 1, 2, 3]),
            new class implements EgressMonotonicClock {
                private int $value = 0;
                public function milliseconds(): int
                {
                    return $this->value++;
                }
            },
        );
    }

    public function profile(): ExternalServiceApplicationProfile
    {
        return $this->applicationProfile;
    }

    public function create(): ExternalServiceApplication
    {
        ++$this->createCalls;
        if (null !== $this->creationFailure) {
            throw $this->creationFailure;
        }
        $networkProfile = $this->applicationProfile->networkEgressProfile();
        $boundary = new class implements GeneratedNetworkEgressApplicationBoundary {
            public function start(NetworkEgressApplicationProfile $profile, EgressResolver $resolver, EgressTransport $transport): NetworkEgressApplication
            {
                return new NetworkEgressApplication($profile, $resolver, $transport);
            }
        };
        $wiring = new LoadedNetworkEgressWiring($boundary, $networkProfile);
        $address = new ResolvedAddress('93.184.216.34');
        $this->resolver = new FakeEndpointResolver(new EgressResolutionResult(new ResolutionEvidence([$address], $address), null));
        $success = new EgressTransportEmission(
            new EgressTransferEvidence(EgressTransferState::ResponseReceived, 1, 1),
            NegotiatedProtocol::Http11,
            0,
            0,
            $this->response,
            null,
        );
        $requestProgress = new EgressTransportEmission(
            new EgressTransferEvidence(EgressTransferState::RequestSent, 1, 0),
            NegotiatedProtocol::Http11,
            0,
            0,
            null,
            null,
        );
        $failureBeforeEffect = new EgressTransportEmission(
            new EgressTransferEvidence(EgressTransferState::None, 0, 0),
            NegotiatedProtocol::Unknown,
            0,
            0,
            null,
            new EgressFailure(EgressFailureKind::Connection, 'transport_failed'),
        );
        $uncertainEffect = new EgressTransportEmission(
            new EgressTransferEvidence(EgressTransferState::RequestSent, 1, 0),
            NegotiatedProtocol::Http11,
            0,
            0,
            null,
            new EgressFailure(EgressFailureKind::Connection, 'transport_failed'),
        );
        $emissions = match ($this->transportMode) {
            'failure_before_effect' => [$failureBeforeEffect, null],
            'uncertain_effect' => [$uncertainEffect, null],
            'request_progress' => [$requestProgress, $success, null],
            'trailing_emission' => [$success, $requestProgress, null],
            default => [$success, null],
        };
        $this->transportSession = new FakeEgressTransportSession($emissions);
        $this->transport = new FakeEgressTransport($this->capabilities, $this->transportSession);
        $reference = $this->applicationProfile->binding()->credentialReference();
        $purpose = $this->context->credentialPurpose();
        $this->lease = new FakeCredentialLease($reference, $purpose, $this->credential);
        $this->credentialSource = new FakeCredentialLeaseSource($this->lease);

        $this->application = new ExternalServiceApplication(
            $this->applicationProfile,
            $wiring,
            $this->resolver,
            $this->transport,
            $reference->source(),
            $this->credentialSource,
        );

        return $this->application;
    }

    public static function completedBody(bool $usage = true): string
    {
        $usageMember = $usage ? ',"usage":{"input_tokens":7,"output_tokens":5,"total_tokens":12}' : '';

        return '{"id":"resp_synthetic_completed","incomplete_details":null,"model":"openai-test-model","object":"response","output":[{"content":[{"annotations":[],"text":"synthetic output","type":"output_text"}],"id":"msg_synthetic_completed","role":"assistant","status":"completed","type":"message"}],"status":"completed"' . $usageMember . '}';
    }

    public static function incompleteBody(string $reason): string
    {
        return '{"id":"resp_synthetic_incomplete","incomplete_details":{"reason":"' . $reason . '"},"model":"openai-test-model","object":"response","output":[{"content":[{"annotations":[],"text":"synthetic partial output","type":"output_text"}],"id":"msg_synthetic_incomplete","role":"assistant","status":"incomplete","type":"message"}],"status":"incomplete","usage":null}';
    }

    public static function streamBody(string $name): string
    {
        $body = (string) file_get_contents(__DIR__ . '/../Fixture/Response/' . $name);

        return str_ends_with($body, "\n\n") ? $body : $body . "\n";
    }

    public function responseBodyReadable(): bool
    {
        return $this->response->getBody()->isReadable();
    }

    private function buildApplicationProfile(): void
    {
        $origin = new CanonicalOrigin($this->endpointScheme, 'api.openai.invalid', OriginScheme::Https === $this->endpointScheme ? 443 : 80);
        $this->capabilities = new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::Tls]);
        $binding = new ServiceBinding(
            new ServiceBindingIdentity($this->identity('binding.openai.responses')),
            new ServiceProfileIdentity($this->identity('service.openai.responses')),
            new ServiceEndpoint($origin, new EndpointBasePath($this->endpointBasePath)),
            new CredentialReference(new CredentialSourceIdentity($this->identity('credentials.openai.responses')), $this->identity('reference.openai.responses')),
            $this->capabilities,
        );
        $bindingSource = new ExternalServiceBindingSource(new ExternalServiceBindingSourceIdentity($this->identity('bindings.openai.responses')), [$binding]);
        $bindingBytes = (new ExternalServiceBindingArtifactBuilder())->build($bindingSource)->canonicalBytes();
        $policy = new EgressPolicySource(
            new EgressPolicyIdentity($this->identity('policy.openai.responses')),
            [$origin],
            AddressScope::PublicOnly,
            OriginScheme::Https === $this->endpointScheme ? TransportSecurity::HttpsOnly : TransportSecurity::ExplicitHttpAllowed,
            RedirectMode::Deny,
            ProxyMode::Deny,
            CookieMode::Deny,
            CredentialMode::OperationScoped,
            new EgressBudget(1, 0, 8, 4096, 4096, 32, 4096, 4096, 4096, 1000, 1000, 5000),
            $this->capabilities,
        );
        $policyBytes = (new EgressPolicyArtifactBuilder())->build($policy)->canonicalBytes();
        $networkProfile = (new NetworkEgressApplicationProfileFactory())->create($policy, $policyBytes);
        $this->applicationProfile = (new ExternalServiceApplicationProfileFactory())->create(
            $bindingSource,
            $bindingBytes,
            $binding->identity(),
            $networkProfile,
        );
    }

    private function identity(string $base): string
    {
        return $base . $this->identitySuffix;
    }
}
