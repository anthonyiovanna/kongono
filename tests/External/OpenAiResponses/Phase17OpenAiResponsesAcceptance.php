<?php

declare(strict_types=1);

namespace Kongono\Tests\External\OpenAiResponses;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileFactory;
use Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplication;
use Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplicationState;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceProfileIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialLease;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseIdentity;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseRequest;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseSource;
use Kongono\ExternalServiceContract\Credential\CredentialLeaseState;
use Kongono\ExternalServiceContract\Credential\CredentialMaterialConsumer;
use Kongono\ExternalServiceContract\Credential\CredentialPurpose;
use Kongono\ExternalServiceContract\Credential\CredentialReference;
use Kongono\ExternalServiceContract\Credential\CredentialSourceIdentity;
use Kongono\ExternalServiceContract\Endpoint\EndpointBasePath;
use Kongono\ExternalServiceContract\Endpoint\ServiceEndpoint;
use Kongono\ExternalServiceContract\Resolution\EndpointResolver;
use Kongono\ExternalServiceCredentialRuntime\Clock\CredentialMonotonicClock;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringGenerator;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringPublisher;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileFactory;
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
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationContract\Result\ActionName;
use Kongono\ModelInvocationContract\Stream\StreamEventKind;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;
use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesExternalServiceApplicationFactory;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationAdapter;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesApplicationProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesFunctionDefinition;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesStructuredOutput;
use Kongono\ModelProviderOpenAiResponses\Profile\ResponseStoragePreference;
use Kongono\ModelProviderOpenAiResponses\Request\ResponsesRequestEncoder;
use Kongono\ModelProviderOpenAiResponses\Response\ResponsesResponseDecoder;
use Kongono\ModelProviderOpenAiResponses\Runtime\OpenAiResponsesApplicationRunInput;
use Kongono\ModelProviderOpenAiResponses\Runtime\OpenAiResponsesApplicationState;
use Kongono\ModelProviderOpenAiResponses\Schema\StrictJsonSchema;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseEventDecoder;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseFrame;
use Kongono\ModelProviderOpenAiResponses\Stream\ResponsesSseStateMachine;
use Kongono\ModelProviderOpenAiResponses\Workflow\OpenAiResponsesApplicationBuildWorkflow;
use Kongono\ModelProviderOpenAiResponsesInspection\Derivation\OpenAiResponsesIntelligenceDeriver;
use Kongono\ModelProviderOpenAiResponsesInspection\Derivation\OpenAiResponsesIntelligenceView;
use Kongono\ModelProviderOpenAiResponsesInspection\Graph\OpenAiResponsesGraphEvidence;
use Kongono\ModelProviderOpenAiResponsesInspection\Graph\OpenAiResponsesGraphEvidenceProjector;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspectionRequest;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspector;
use Kongono\ModelProviderOpenAiResponsesInspection\Projection\OpenAiResponsesObservationProjector;
use Kongono\ModelProviderOpenAiResponsesInspection\Projection\OpenAiResponsesSecurityAuditProjector;
use Kongono\ModelProviderOpenAiResponsesInspection\Role\OpenAiResponsesInspectionDeveloperRole;
use Kongono\ModelProviderOpenAiResponsesInspection\Verification\OpenAiResponsesIntelligenceVerdict;
use Kongono\ModelProviderOpenAiResponsesInspection\Verification\OpenAiResponsesIntelligenceVerifier;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressApplication\Runtime\LoadedNetworkEgressWiring;
use Kongono\NetworkEgressApplication\Workflow\NetworkEgressApplicationBuildWorkflow;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressCurl\Profile\CurlTlsTrust;
use Kongono\NetworkEgressCurl\Profile\CurlTransportProfile;
use Kongono\NetworkEgressCurl\Transport\CurlEgressTransport;
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
use Kongono\NetworkEgressRuntime\Authority\EgressAuthorityCurrentness;
use Kongono\NetworkEgressRuntime\Cancellation\EgressCancellation;
use Kongono\NetworkEgressRuntime\Clock\EgressMonotonicClock;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;
use Kongono\Observability\ObservabilityContext;
use RuntimeException;
use Throwable;

final class Phase17AuthorityCurrentness implements EgressAuthorityCurrentness
{
    public int $calls = 0;

    public function isCurrent(EgressAuthorityReference $reference, CanonicalOrigin $origin): bool
    {
        unset($reference, $origin);
        ++$this->calls;

        return true;
    }
}

final class Phase17EgressCancellation implements EgressCancellation
{
    public int $calls = 0;

    public function isCancellationRequested(): bool
    {
        ++$this->calls;

        return false;
    }
}

final class Phase17CredentialClock implements CredentialMonotonicClock
{
    private int $value = 0;

    public function milliseconds(): int
    {
        return $this->value++;
    }
}

final class Phase17EgressClock implements EgressMonotonicClock
{
    private int $value = 0;

    public function milliseconds(): int
    {
        return $this->value++;
    }
}

final class Phase17InvocationClock implements InvocationMonotonicClock
{
    private int $value = 0;

    public function milliseconds(): int
    {
        return $this->value++;
    }
}

final readonly class Phase17InvocationCancellation implements InvocationCancellation
{
    public function __construct(private bool $requested = false) {}

    public function isCancellationRequested(): bool
    {
        return $this->requested;
    }
}

final class Phase17EndpointResolver implements EndpointResolver
{
    public int $calls = 0;

    public function resolve(CanonicalOrigin $origin): EgressResolutionResult
    {
        unset($origin);
        ++$this->calls;
        $address = new ResolvedAddress('127.0.0.1');

        return new EgressResolutionResult(new ResolutionEvidence([$address], $address), null);
    }
}

final class Phase17CredentialLease implements CredentialLease
{
    public int $materialCalls = 0;
    public int $retireCalls = 0;
    private CredentialLeaseState $state = CredentialLeaseState::Acquired;

    public function __construct(
        private readonly CredentialReference $reference,
        private readonly CredentialPurpose $purpose,
        private readonly string $identity,
    ) {}

    public function identity(): CredentialLeaseIdentity
    {
        return new CredentialLeaseIdentity($this->identity);
    }

    public function reference(): CredentialReference
    {
        return $this->reference;
    }

    public function purpose(): CredentialPurpose
    {
        return $this->purpose;
    }

    public function state(): CredentialLeaseState
    {
        return $this->state;
    }

    public function withMaterial(CredentialMaterialConsumer $consumer): void
    {
        ++$this->materialCalls;
        $this->state = CredentialLeaseState::InUse;
        try {
            $consumer->consume(Phase17OpenAiResponsesAcceptance::fixtureCredential());
        } finally {
            $this->state = CredentialLeaseState::Acquired;
        }
    }

    public function retire(): void
    {
        ++$this->retireCalls;
        $this->state = CredentialLeaseState::Retiring;
        $this->state = CredentialLeaseState::Retired;
    }
}

final class Phase17CredentialSource implements CredentialLeaseSource
{
    public int $calls = 0;

    public function __construct(private readonly Phase17CredentialLease $lease) {}

    public function acquire(CredentialLeaseRequest $request): CredentialLease
    {
        unset($request);
        ++$this->calls;

        return $this->lease;
    }
}

final class Phase17ExternalApplicationFactory implements OpenAiResponsesExternalServiceApplicationFactory
{
    public int $createCalls = 0;
    public readonly Phase17EndpointResolver $resolver;
    public readonly Phase17CredentialLease $lease;
    public readonly Phase17CredentialSource $credentialSource;
    public ?ExternalServiceApplication $application = null;

    public function __construct(
        private readonly ExternalServiceApplicationProfile $profile,
        private readonly LoadedNetworkEgressWiring $wiring,
        private readonly CredentialSourceIdentity $sourceIdentity,
        CredentialReference $reference,
        CredentialPurpose $purpose,
        private readonly string $certificate,
        string $suffix,
    ) {
        $this->resolver = new Phase17EndpointResolver();
        $this->lease = new Phase17CredentialLease($reference, $purpose, 'lease.phase17.' . $suffix);
        $this->credentialSource = new Phase17CredentialSource($this->lease);
    }

    public function profile(): ExternalServiceApplicationProfile
    {
        return $this->profile;
    }

    public function create(): ExternalServiceApplication
    {
        ++$this->createCalls;
        if (1 !== $this->createCalls) {
            throw new RuntimeException('phase17_factory_failed');
        }
        $transport = new CurlEgressTransport(new CurlTransportProfile(new CurlTlsTrust($this->certificate), true));
        $this->application = new ExternalServiceApplication(
            $this->profile,
            $this->wiring,
            $this->resolver,
            $transport,
            $this->sourceIdentity,
            $this->credentialSource,
        );

        return $this->application;
    }
}

final readonly class Phase17ProfileContext
{
    public function __construct(
        public string $suffix,
        public ResultMode $mode,
        public bool $streaming,
        public InvocationPlanSource $source,
        public string $artifactBytes,
        public OpenAiResponsesProfile $providerProfile,
        public ExternalServiceApplicationProfile $externalProfile,
        public EgressPolicySource $policySource,
        public string $policyArtifactBytes,
        public CredentialSourceIdentity $credentialSourceIdentity,
        public CredentialReference $credentialReference,
        public CredentialPurpose $credentialPurpose,
    ) {}
}

final class Phase17OpenAiResponsesAcceptance
{
    private const int STREAM_LIMIT = 1_048_576;
    private const int PROCESS_TIMEOUT_SECONDS = 30;
    private const string FAILURE = "phase17_acceptance_failed\n";
    private const string HOST = 'api.openai.invalid';
    private const string SCHEMA = '{"additionalProperties":false,"properties":{"answer":{"type":"string"}},"required":["answer"],"type":"object"}';
    private int $caseCount = 0;

    public function __construct(private readonly string $repositoryRoot) {}

    /** @param list<string> $arguments
     * @param resource $stdout
     * @param resource $stderr
     */
    public function run(array $arguments, $stdout, $stderr): int
    {
        try {
            if (['--child-success'] === $arguments) {
                return $this->successChild($stdout);
            }
            if (['--child-failure'] === $arguments) {
                $workspace = new TemporaryOpenAiResponsesWorkspace($this->repositoryRoot);
                try {
                    throw new RuntimeException('private phase17 failure sentinel');
                } finally {
                    $workspace->cleanup();
                }
            }
            if (['--child-malformed'] === $arguments) {
                fwrite($stdout, "{}\n");

                return 0;
            }
            if (['--child-overflow-stdout'] === $arguments) {
                fwrite($stdout, str_repeat('x', self::STREAM_LIMIT + 1));

                return 0;
            }
            if (['--child-overflow-stderr'] === $arguments) {
                fwrite($stderr, str_repeat('x', self::STREAM_LIMIT + 1));

                return 1;
            }
            if (['--child-timeout'] === $arguments) {
                sleep(5);

                return 0;
            }
            if (3 === count($arguments) && '--responder' === $arguments[0]) {
                return $this->respond($stdout, $arguments[1], $arguments[2]);
            }
            if ([] !== $arguments) {
                throw new RuntimeException('invalid phase17 mode');
            }

            $before = $this->repositoryFingerprint();
            $residue = $this->temporaryResidue();
            $single = $this->child('--child-success');
            $failure = $this->child('--child-failure');
            $later = $this->child('--child-success');
            $sequential = $this->child('--child-success');
            [$concurrentA, $concurrentB] = $this->childPair();
            $malformed = $this->child('--child-malformed');
            $this->require($this->fails(fn() => $this->child('--child-overflow-stdout')));
            $this->require($this->fails(fn() => $this->child('--child-overflow-stderr')));
            $this->require($this->fails(fn() => $this->child('--child-timeout', 1)));
            $this->require(0 === $single->exitCode && '' === $single->stderr && $this->isCanonicalSuccess($single->stdout));
            $this->require(0 !== $failure->exitCode && '' === $failure->stdout && self::FAILURE === $failure->stderr);
            $this->require(0 === $later->exitCode && $single->stdout === $later->stdout && '' === $later->stderr);
            $this->require(0 === $sequential->exitCode && $single->stdout === $sequential->stdout && '' === $sequential->stderr);
            $this->require(0 === $concurrentA->exitCode && 0 === $concurrentB->exitCode);
            $this->require($single->stdout === $concurrentA->stdout && $concurrentA->stdout === $concurrentB->stdout);
            $this->require('' === $concurrentA->stderr && '' === $concurrentB->stderr);
            $this->require(0 === $malformed->exitCode && !$this->isCanonicalSuccess($malformed->stdout));
            $this->require($before === $this->repositoryFingerprint());
            $this->require($residue === $this->temporaryResidue());
            fwrite($stdout, $single->stdout);

            return 0;
        } catch (Throwable) {
            fwrite($stderr, self::FAILURE);

            return 1;
        }
    }

    public static function fixtureCredential(): string
    {
        return hash('sha256', 'kongono-phase17-inert-credential-fixture');
    }

    /** @param resource $stdout */
    private function successChild($stdout): int
    {
        if (!extension_loaded('curl') || !extension_loaded('sockets') || !extension_loaded('openssl')) {
            throw new RuntimeException('phase17_extension_failed');
        }
        $workspace = new TemporaryOpenAiResponsesWorkspace($this->repositoryRoot);
        $responder = null;
        try {
            [$certificatePath, $privateKeyPath, $certificate] = $this->tlsAssets($workspace);
            $responder = $this->open([PHP_BINARY, __DIR__ . '/run.php', '--responder', $certificatePath, $privateKeyPath]);
            $port = $this->responderPort($responder);
            $results = [];
            $profiles = [];
            foreach ([
                ['text', ResultMode::Text, false],
                ['stream', ResultMode::Text, true],
                ['structured', ResultMode::Structured, false],
                ['action', ResultMode::ActionSuggestion, false],
            ] as [$suffix, $mode, $streaming]) {
                $context = $this->profile($suffix, $mode, $streaming, $port);
                [$result, $profile] = $this->execute($workspace, $context, $certificate);
                $results[] = $result;
                $profiles[] = $profile;
            }
            $response = $this->close($responder);
            $responder = null;
            $this->case(0 === $response->exitCode && '' === $response->stdout && '' === $response->stderr);
            $this->cancellationFailure($workspace, $port, $certificate);
            $this->offlineFailures($profiles[0], $results[0]);
            $this->case($this->fails(fn() => (new OpenAiResponsesInspector())->inspect(new OpenAiResponsesInspectionRequest($profiles[0], $results[1]))));
        } finally {
            if (is_array($responder)) {
                try {
                    $this->close($responder);
                } catch (Throwable) {
                }
            }
            $workspace->cleanup();
        }
        fwrite($stdout, self::successBytes($this->caseCount));

        return 0;
    }

    /** @return array{string,string,string} */
    private function tlsAssets(TemporaryOpenAiResponsesWorkspace $workspace): array
    {
        $configuration = "[req]\nprompt=no\ndistinguished_name=dn\nreq_extensions=v3_req\nx509_extensions=v3_req\n[dn]\nCN=" . self::HOST . "\n[v3_req]\nsubjectAltName=DNS:" . self::HOST . "\nbasicConstraints=critical,CA:TRUE\nkeyUsage=critical,digitalSignature,keyEncipherment,keyCertSign\nextendedKeyUsage=serverAuth\n";
        $configurationPath = $workspace->write('openssl.cnf', $configuration);
        $arguments = ['config' => $configurationPath, 'digest_alg' => 'sha256', 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'req_extensions' => 'v3_req', 'x509_extensions' => 'v3_req'];
        $key = openssl_pkey_new($arguments);
        if (!$key instanceof \OpenSSLAsymmetricKey) {
            throw new RuntimeException('phase17_tls_failed');
        }
        $csrKey = $key;
        $csr = openssl_csr_new(['commonName' => self::HOST], $csrKey, $arguments);
        if (!$csr instanceof \OpenSSLCertificateSigningRequest) {
            throw new RuntimeException('phase17_tls_failed');
        }
        $certificateResource = openssl_csr_sign($csr, null, $key, 2, $arguments);
        $privateKey = '';
        $certificate = '';
        if (!$certificateResource instanceof \OpenSSLCertificate
            || !openssl_pkey_export($key, $privateKey, null, $arguments)
            || !openssl_x509_export($certificateResource, $certificate)
            || !is_string($privateKey)
            || !is_string($certificate)
        ) {
            throw new RuntimeException('phase17_tls_failed');
        }

        return [$workspace->write('server.crt', $certificate), $workspace->write('server.key', $privateKey), $certificate];
    }

    private function profile(string $suffix, ResultMode $mode, bool $streaming, int $port): Phase17ProfileContext
    {
        $providerCapabilities = match ($mode) {
            ResultMode::Text => $streaming
                ? [ProviderCapability::TextResult, ProviderCapability::Streaming, ProviderCapability::UsageEvidence]
                : [ProviderCapability::TextResult, ProviderCapability::UsageEvidence],
            ResultMode::Structured => [ProviderCapability::StructuredResult, ProviderCapability::UsageEvidence],
            ResultMode::ActionSuggestion => [ProviderCapability::ActionSuggestion, ProviderCapability::UsageEvidence],
        };
        $neutral = new ModelProfileReference(
            ContractVersion::Version1,
            new ProfileIdentity('profile.phase17.' . $suffix),
            new ProviderIdentity('openai'),
            new ModelIdentity('openai-test-model'),
            new ProviderCapabilitySet($providerCapabilities),
        );
        $schema = new StrictJsonSchema(self::SCHEMA);
        $providerProfile = new OpenAiResponsesProfile(
            $neutral,
            ResultMode::Text === $mode ? OpenAiResponsesMappingVersion::Version1 : OpenAiResponsesMappingVersion::Version2,
            ResponseStoragePreference::Disabled,
            ResultMode::Structured === $mode ? new OpenAiResponsesStructuredOutput('answer_format', $schema) : null,
            ResultMode::ActionSuggestion === $mode ? new OpenAiResponsesFunctionDefinition(new ActionName('record_answer'), $schema) : null,
        );
        $request = new InvocationRequest(
            ContractVersion::Version1,
            new InvocationIdentity('invocation.phase17.' . $suffix),
            $neutral,
            [new InputMessage(InputRole::User, ModelVisibility::Private, 'synthetic input')],
            $mode,
            $streaming,
            new InvocationLimits(4096, $streaming ? 5 : 1, 3),
        );
        $source = new InvocationPlanSource(
            new InvocationPlanIdentity('plan.phase17.' . $suffix),
            new AdapterIdentity(OpenAiResponsesInvocationAdapter::IDENTITY),
            $request,
            new InvocationExecutionBudget(10_000, 1, 65_536, [
                new UsageLimit(new UsageMetricName('input_tokens'), new UsageUnitName('count'), 1000),
                new UsageLimit(new UsageMetricName('output_tokens'), new UsageUnitName('count'), 1000),
                new UsageLimit(new UsageMetricName('total_tokens'), new UsageUnitName('count'), 2000),
            ], null),
        );
        $artifactBytes = (new InvocationPlanArtifactBuilder())->build($source)->canonicalBytes();
        $origin = new CanonicalOrigin(OriginScheme::Https, self::HOST, $port);
        $capabilities = new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::Tls]);
        $sourceIdentity = new CredentialSourceIdentity('credentials.phase17.' . $suffix);
        $reference = new CredentialReference($sourceIdentity, 'reference.phase17.' . $suffix);
        $purpose = new CredentialPurpose('invoke.phase17.' . $suffix);
        $binding = new ServiceBinding(
            new ServiceBindingIdentity('binding.phase17.' . $suffix),
            new ServiceProfileIdentity('service.phase17.' . $suffix),
            new ServiceEndpoint($origin, new EndpointBasePath('')),
            $reference,
            $capabilities,
        );
        $bindingSource = new ExternalServiceBindingSource(new ExternalServiceBindingSourceIdentity('bindings.phase17.' . $suffix), [$binding]);
        $bindingBytes = (new ExternalServiceBindingArtifactBuilder())->build($bindingSource)->canonicalBytes();
        $policy = new EgressPolicySource(
            new EgressPolicyIdentity('policy.phase17.' . $suffix),
            [$origin],
            AddressScope::ExplicitPrivateAllowed,
            TransportSecurity::ExplicitHttpAllowed,
            RedirectMode::Deny,
            ProxyMode::Deny,
            CookieMode::Deny,
            CredentialMode::OperationScoped,
            new EgressBudget(1, 0, 8, 65_536, 65_536, 32, 8192, 65_536, 8192, 5000, 5000, 10_000),
            $capabilities,
        );
        $policyBytes = (new EgressPolicyArtifactBuilder())->build($policy)->canonicalBytes();
        $networkProfile = (new NetworkEgressApplicationProfileFactory())->create($policy, $policyBytes);
        $externalProfile = (new ExternalServiceApplicationProfileFactory())->create($bindingSource, $bindingBytes, $binding->identity(), $networkProfile);

        return new Phase17ProfileContext($suffix, $mode, $streaming, $source, $artifactBytes, $providerProfile, $externalProfile, $policy, $policyBytes, $sourceIdentity, $reference, $purpose);
    }

    /** @return array{InvocationRuntimeResult,OpenAiResponsesApplicationProfile} */
    private function execute(TemporaryOpenAiResponsesWorkspace $workspace, Phase17ProfileContext $context, string $certificate): array
    {
        $networkRoot = $workspace->directory('network-' . $context->suffix);
        $this->require(mkdir($networkRoot . '/generated', 0700));
        $networkBuild = (new NetworkEgressApplicationBuildWorkflow())->build(
            $networkRoot,
            'generated',
            $context->policySource,
            $context->policyArtifactBytes,
        );
        $factory = new Phase17ExternalApplicationFactory(
            $context->externalProfile,
            $networkBuild->loadedWiring(),
            $context->credentialSourceIdentity,
            $context->credentialReference,
            $context->credentialPurpose,
            $certificate,
            $context->suffix,
        );
        $providerRoot = $workspace->directory('provider-' . $context->suffix);
        $output = $providerRoot . '/generated';
        $this->require(mkdir($output, 0700));
        $artifactFile = $providerRoot . '/invocation-plan.json';
        $this->require(strlen($context->artifactBytes) === file_put_contents($artifactFile, $context->artifactBytes, LOCK_EX));
        $workflow = new OpenAiResponsesApplicationBuildWorkflow();
        if ('stream' === $context->suffix) {
            $modelProfile = (new ModelInvocationApplicationProfileFactory())->create($context->source, $context->artifactBytes);
            $generated = (new ModelInvocationWiringGenerator())->generate($modelProfile);
            (new ModelInvocationWiringPublisher())->publish($providerRoot, $output, $generated);
            $beforeCheck = $this->directoryIdentity($providerRoot);
            $current = $workflow->check($providerRoot, $artifactFile, $context->source, $output, $context->providerProfile, $context->externalProfile);
            $this->case($beforeCheck === $this->directoryIdentity($providerRoot));
        } else {
            $current = $workflow->build($providerRoot, $artifactFile, $context->source, $output, $context->providerProfile, $context->externalProfile);
        }
        if ('text' === $context->suffix) {
            $this->require(strlen($context->artifactBytes) === file_put_contents($artifactFile, substr($context->artifactBytes, 0, -1) . 'x', LOCK_EX));
            $this->case($this->fails(fn() => $workflow->check($providerRoot, $artifactFile, $context->source, $output, $context->providerProfile, $context->externalProfile)));
            $this->require(strlen($context->artifactBytes) === file_put_contents($artifactFile, $context->artifactBytes, LOCK_EX));
        }
        $authority = new Phase17AuthorityCurrentness();
        $egressCancellation = new Phase17EgressCancellation();
        $input = new OpenAiResponsesApplicationRunInput(
            $factory,
            new \Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity('operation.phase17.' . $context->suffix),
            $context->credentialPurpose,
            5000,
            new EgressReceiptIdentity('egress-receipt.phase17.' . $context->suffix),
            new EgressAttemptIdentity('attempt.phase17.' . $context->suffix),
            new EgressAuthorityReference('authority.phase17.' . $context->suffix, 1),
            $authority,
            $egressCancellation,
            new Phase17EgressClock(),
            new Phase17CredentialClock(),
            new ReceiptIdentity('invocation-receipt.phase17.' . $context->suffix),
            new Phase17InvocationCancellation(),
            new Phase17InvocationClock(),
        );
        $application = $current->application();
        $result = $application->run($input);
        $modelResult = $result->result();
        if (null === $modelResult) {
            throw new RuntimeException('phase17_acceptance_failed');
        }
        $this->case('succeeded' === $result->receipt()->outcome()->value);
        $this->case($context->mode === $modelResult->mode());
        $this->case(1 === $factory->createCalls && 1 === $factory->resolver->calls);
        $this->case(1 === $factory->credentialSource->calls && 1 === $factory->lease->materialCalls && 1 === $factory->lease->retireCalls);
        $this->case($factory->application instanceof ExternalServiceApplication && ExternalServiceApplicationState::Retired === $factory->application->state());
        $this->case(OpenAiResponsesApplicationState::Retired === $application->state() && !$application->hasActiveOperation());
        $this->case($authority->calls >= 2 && $egressCancellation->calls > 0);
        $quantities = $modelResult->usage()?->quantities() ?? [];
        $this->case(['input_tokens', 'output_tokens', 'total_tokens'] === array_map(static fn($quantity): string => $quantity->metric()->value(), $quantities));
        $this->case([7, 5, 12] === array_map(static fn($quantity): int => $quantity->quantity(), $quantities));
        if ('text' === $context->suffix) {
            $this->case('synthetic output' === $modelResult->text()?->value() && [] === $result->events());
        } elseif ('stream' === $context->suffix) {
            $this->case('synthetic-stream-output' === $modelResult->text()?->value());
            $this->case([StreamEventKind::Started, StreamEventKind::ContentDelta, StreamEventKind::ContentDelta, StreamEventKind::Usage, StreamEventKind::Completed] === array_map(static fn($event): StreamEventKind => $event->kind(), $result->events()));
            $this->case([1, 2, 3, 4, 5] === array_map(static fn($event): int => $event->sequence()->value(), $result->events()));
        } elseif ('structured' === $context->suffix) {
            $this->case('{"answer":"accepted"}' === $modelResult->text()?->value() && null === $modelResult->actionSuggestion());
        } else {
            $suggestion = $modelResult->actionSuggestion();
            $this->case(null === $modelResult->text() && 'record_answer' === $suggestion?->name()->value());
            $this->case('{"answer":"accepted"}' === $suggestion?->arguments()->value());
        }
        $this->case($this->fails(fn() => $application->run($input)));
        $profile = $current->profile();
        $this->inspect($profile, $result);

        return [$result, $profile];
    }

    private function cancellationFailure(TemporaryOpenAiResponsesWorkspace $workspace, int $port, string $certificate): void
    {
        $context = $this->profile('cancel', ResultMode::Text, false, $port);
        $networkRoot = $workspace->directory('network-cancel');
        $this->require(mkdir($networkRoot . '/generated', 0700));
        $networkBuild = (new NetworkEgressApplicationBuildWorkflow())->build(
            $networkRoot,
            'generated',
            $context->policySource,
            $context->policyArtifactBytes,
        );
        $factory = new Phase17ExternalApplicationFactory(
            $context->externalProfile,
            $networkBuild->loadedWiring(),
            $context->credentialSourceIdentity,
            $context->credentialReference,
            $context->credentialPurpose,
            $certificate,
            $context->suffix,
        );
        $providerRoot = $workspace->directory('provider-cancel');
        $output = $providerRoot . '/generated';
        $this->require(mkdir($output, 0700));
        $artifactFile = $providerRoot . '/invocation-plan.json';
        $this->require(strlen($context->artifactBytes) === file_put_contents($artifactFile, $context->artifactBytes, LOCK_EX));
        $current = (new OpenAiResponsesApplicationBuildWorkflow())->build(
            $providerRoot,
            $artifactFile,
            $context->source,
            $output,
            $context->providerProfile,
            $context->externalProfile,
        );
        $input = new OpenAiResponsesApplicationRunInput(
            $factory,
            new \Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity('operation.phase17.cancel'),
            $context->credentialPurpose,
            5000,
            new EgressReceiptIdentity('egress-receipt.phase17.cancel'),
            new EgressAttemptIdentity('attempt.phase17.cancel'),
            new EgressAuthorityReference('authority.phase17.cancel', 1),
            new Phase17AuthorityCurrentness(),
            new Phase17EgressCancellation(),
            new Phase17EgressClock(),
            new Phase17CredentialClock(),
            new ReceiptIdentity('invocation-receipt.phase17.cancel'),
            new Phase17InvocationCancellation(true),
            new Phase17InvocationClock(),
        );
        $application = $current->application();
        $result = $application->run($input);
        $this->case('cancelled' === $result->receipt()->outcome()->value && null === $result->result() && [] === $result->events());
        $this->case(0 === $factory->createCalls && 0 === $factory->credentialSource->calls && 0 === $factory->resolver->calls);
        $this->case(OpenAiResponsesApplicationState::Retired === $application->state() && !$application->hasActiveOperation());
        $this->case($this->fails(fn() => $application->run($input)));
    }

    private function inspect(OpenAiResponsesApplicationProfile $profile, InvocationRuntimeResult $result): void
    {
        $inspector = new OpenAiResponsesInspector();
        $inspection = $inspector->inspect(new OpenAiResponsesInspectionRequest($profile, $result));
        $again = $inspector->inspect(new OpenAiResponsesInspectionRequest($profile, $result));
        $this->case($inspection->canonicalBytes() === $again->canonicalBytes() && $inspection->runtimeEvidenceSupplied());
        foreach (['synthetic input', 'synthetic output', 'synthetic-stream-output', '{"answer":"accepted"}', self::fixtureCredential(), self::HOST] as $hidden) {
            $this->case(!str_contains($inspection->canonicalBytes(), $hidden));
        }
        foreach (OpenAiResponsesInspectionDeveloperRole::cases() as $role) {
            $graph = (new OpenAiResponsesGraphEvidenceProjector())->project($inspection, $role);
            $view = (new OpenAiResponsesIntelligenceDeriver())->derive($inspection, $role);
            $verification = (new OpenAiResponsesIntelligenceVerifier())->verify($inspection, $view);
            $this->case(OpenAiResponsesGraphEvidence::RELATIONSHIP === $graph->relationship() && !$graph->supplied());
            $this->case(OpenAiResponsesIntelligenceVerdict::Pass === $verification->verdict() && [] === $verification->failureTokens());
            $this->case(in_array($view->purpose(), ['explain', 'verify', 'teach'], true));
            $this->case('not-performed' === $view->roleExecution() && 'not-performed' === $view->networking());
            $this->case('not-performed' === $view->recording() && 'not-performed' === $view->exporting() && 'not-performed' === $view->certification());
        }
        $context = ObservabilityContext::uncorrelated();
        $observation = (new OpenAiResponsesObservationProjector())->project($inspection, $context);
        $audit = (new OpenAiResponsesSecurityAuditProjector())->project($inspection, $context);
        $this->case('kongono.openai-responses.inspection' === $observation->identity()->name());
        $this->case('kongono.openai-responses.inspection.security' === $audit->definition()->identity()->name() && DiagnosticDisclosure::Safe === $audit->disclosure());
        $tampered = new OpenAiResponsesIntelligenceView(
            OpenAiResponsesInspectionDeveloperRole::Chie,
            'teach',
            $inspection,
            new OpenAiResponsesGraphEvidence($inspection->identity(), OpenAiResponsesInspectionDeveloperRole::Chie, false),
            roleExecution: 'not-observed',
            serializationSha256: str_repeat('0', 64),
        );
        $this->case(OpenAiResponsesIntelligenceVerdict::Fail === (new OpenAiResponsesIntelligenceVerifier())->verify($inspection, $tampered)->verdict());
    }

    private function offlineFailures(OpenAiResponsesApplicationProfile $profile, InvocationRuntimeResult $result): void
    {
        unset($result);
        $request = $profile->modelInvocationProfile()->source()->request();
        $provider = $profile->providerProfile();
        $encoder = new ResponsesRequestEncoder();
        $encoded = $encoder->encode($request, $provider);
        $this->case(self::expectedRequest('text') === $encoded->body());
        $decoder = new ResponsesResponseDecoder();
        $valid = self::textResponse();
        $this->case('synthetic output' === $decoder->decode($request, $provider, $valid)->text()->value());
        foreach ([
            '{',
            str_replace('"status":"completed"', '"status":"completed","status":"completed"', $valid),
            str_replace('openai-test-model', 'foreign-model', $valid),
            str_replace('"type":"message"', '"type":"web_search_call"', $valid),
            str_replace('"annotations":[]', '"annotations":[{}]', $valid),
            str_replace('{"annotations":[],"text":"synthetic output","type":"output_text"}', '{"refusal":"private-refusal","type":"refusal"}', $valid),
            str_replace('"status":"completed"', '"status":"failed"', $valid),
            str_replace('"status":"completed"', '"status":"incomplete"', $valid),
        ] as $invalid) {
            $this->case($this->fails(fn() => $decoder->decode($request, $provider, $invalid)));
        }
        $this->case($this->fails(fn() => $decoder->decode($request, $provider, str_repeat('x', ResponsesResponseDecoder::MAXIMUM_BODY_BYTES + 1))));
        $frameDecoder = new ResponsesSseEventDecoder();
        $createdData = '{"response":{"id":"resp_stream_completed","model":"openai-test-model","object":"response","output":[],"status":"in_progress"},"sequence_number":0,"type":"response.created"}';
        $created = $frameDecoder->decode(new ResponsesSseFrame('response.created', $createdData));
        $machine = new ResponsesSseStateMachine($request, $provider);
        $this->case('started' === $machine->accept($created));
        $this->case($this->fails(fn() => $machine->accept($created)));
        $this->case($this->fails(fn() => $frameDecoder->decode(new ResponsesSseFrame(null, '{"sequence_number":0,"type":"response.created","type":"response.failed"}'))));
        $failedTerminal = 'data: {"response":{"error":{"code":"private_code","message":"private provider failure"},"id":"resp_stream_completed","model":"openai-test-model","object":"response","status":"failed"},"sequence_number":9,"type":"response.failed"}' . "\n\n";
        $failedBytes = preg_replace('/data: \{"response":.*"type":"response\.completed"\}\n\n\z/D', $failedTerminal, self::streamResponse());
        if (!is_string($failedBytes)) {
            throw new RuntimeException('phase17_acceptance_failed');
        }
        $failedMachine = new ResponsesSseStateMachine($request, $provider);
        $signal = 'none';
        $last = null;
        foreach ($this->sseFrames($failedBytes) as $frame) {
            $last = $frameDecoder->decode($frame);
            $signal = $failedMachine->accept($last);
        }
        $this->case('failed' === $signal && $failedMachine->isTerminal() && null === $failedMachine->terminalResponse());
        $this->case(null !== $last && $this->fails(fn() => $failedMachine->accept($last)));
        $this->case($this->fails(fn() => new StrictJsonSchema('{"type":"object","additionalProperties":true}')));
    }

    /** @return list<ResponsesSseFrame> */
    private function sseFrames(string $bytes): array
    {
        $frames = [];
        foreach (explode("\n\n", rtrim($bytes, "\n")) as $block) {
            $event = null;
            $data = null;
            foreach (explode("\n", $block) as $line) {
                if (str_starts_with($line, 'event: ')) {
                    $event = substr($line, 7);
                } elseif (str_starts_with($line, 'data: ')) {
                    $data = substr($line, 6);
                } else {
                    throw new RuntimeException('phase17_acceptance_failed');
                }
            }
            if (null === $data) {
                throw new RuntimeException('phase17_acceptance_failed');
            }
            $frames[] = new ResponsesSseFrame($event, $data);
        }

        return $frames;
    }

    /** @param resource $stdout */
    private function respond($stdout, string $certificatePath, string $privateKeyPath): int
    {
        TemporaryOpenAiResponsesWorkspace::assertOwnedFile($certificatePath);
        TemporaryOpenAiResponsesWorkspace::assertOwnedFile($privateKeyPath);
        $context = stream_context_create(['ssl' => [
            'local_cert' => $certificatePath,
            'local_pk' => $privateKeyPath,
            'verify_peer' => false,
            'allow_self_signed' => true,
            'crypto_method' => STREAM_CRYPTO_METHOD_TLS_SERVER,
        ]]);
        $server = @stream_socket_server('tls://127.0.0.1:0', $errorCode, $errorMessage, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
        unset($errorCode, $errorMessage);
        if (!is_resource($server)) {
            throw new RuntimeException('phase17_responder_failed');
        }
        try {
            $name = stream_socket_get_name($server, false);
            if (false === $name || 1 !== preg_match('/\A127\.0\.0\.1:([1-9][0-9]{0,4})\z/D', $name, $match) || (int) $match[1] > 65_535) {
                throw new RuntimeException('phase17_responder_failed');
            }
            $port = (int) $match[1];
            fwrite($stdout, 'READY:' . $port . "\n");
            fflush($stdout);
            foreach (['text', 'stream', 'structured', 'action'] as $profile) {
                $client = @stream_socket_accept($server, 5);
                if (!is_resource($client)) {
                    throw new RuntimeException('phase17_responder_failed');
                }
                try {
                    stream_set_timeout($client, 5);
                    [$headers, $body] = $this->readRequest($client);
                    $expectedAccept = 'stream' === $profile ? 'text/event-stream' : 'application/json';
                    $this->require(['POST /v1/responses HTTP/1.1'] === $headers[':request']);
                    $this->require([self::HOST . ':' . $port] === ($headers['host'] ?? []));
                    $this->require(['Bearer ' . self::fixtureCredential()] === ($headers['authorization'] ?? []));
                    $this->require(['application/json'] === ($headers['content-type'] ?? []));
                    $this->require([$expectedAccept] === ($headers['accept'] ?? []));
                    $this->require(self::expectedRequest($profile) === $body);
                    $responseBody = match ($profile) {
                        'text' => self::textResponse(),
                        'stream' => self::streamResponse(),
                        'structured' => self::structuredResponse(),
                        'action' => self::actionResponse(),
                    };
                    $contentType = 'stream' === $profile ? 'text/event-stream' : 'application/json';
                    $response = sprintf("HTTP/1.1 200 OK\r\nContent-Length: %d\r\nContent-Type: %s\r\nConnection: close\r\n\r\n%s", strlen($responseBody), $contentType, $responseBody);
                    $this->require(strlen($response) <= 65_536);
                    $written = 0;
                    while ($written < strlen($response)) {
                        $count = fwrite($client, substr($response, $written));
                        if (false === $count || 0 === $count) {
                            throw new RuntimeException('phase17_responder_failed');
                        }
                        $written += $count;
                    }
                    fflush($client);
                } finally {
                    fclose($client);
                }
            }

            return 0;
        } finally {
            fclose($server);
        }
    }

    /** @param resource $client
     * @return array{array<string,list<string>>,string}
     */
    private function readRequest($client): array
    {
        $bytes = '';
        while (!str_contains($bytes, "\r\n\r\n")) {
            $chunk = fread($client, 4096);
            if (false === $chunk || '' === $chunk) {
                $metadata = stream_get_meta_data($client);
                if ($metadata['timed_out'] || feof($client)) {
                    throw new RuntimeException('phase17_responder_failed');
                }
                continue;
            }
            $bytes .= $chunk;
            $this->require(strlen($bytes) <= 65_536);
        }
        [$head, $body] = explode("\r\n\r\n", $bytes, 2);
        $lines = explode("\r\n", $head);
        $requestLine = array_shift($lines);
        $headers = [':request' => [$requestLine]];
        foreach ($lines as $line) {
            $position = strpos($line, ':');
            if (false === $position) {
                throw new RuntimeException('phase17_responder_failed');
            }
            $name = strtolower(substr($line, 0, $position));
            $headers[$name][] = trim(substr($line, $position + 1));
        }
        $lengthValues = $headers['content-length'] ?? [];
        $this->require(1 === count($lengthValues) && 1 === preg_match('/\A[1-9][0-9]*\z/D', $lengthValues[0]));
        $length = (int) $lengthValues[0];
        $this->require($length <= 65_536 && !isset($headers['transfer-encoding']));
        while (strlen($body) < $length) {
            $remaining = $length - strlen($body);
            if ($remaining <= 0) {
                break;
            }
            $chunk = fread($client, min(4096, $remaining));
            if (false === $chunk || '' === $chunk) {
                $metadata = stream_get_meta_data($client);
                if ($metadata['timed_out'] || feof($client)) {
                    throw new RuntimeException('phase17_responder_failed');
                }
                continue;
            }
            $body .= $chunk;
        }
        $this->require(strlen($body) === $length);

        return [$headers, $body];
    }

    /** @param array{resource,array<int,resource>} $opened */
    private function responderPort(array $opened): int
    {
        [, $pipes] = $opened;
        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            $line = fgets($pipes[1], 65);
            if (false !== $line) {
                if (1 !== preg_match('/\AREADY:([1-9][0-9]{0,4})\n\z/D', $line, $match) || (int) $match[1] > 65_535) {
                    throw new RuntimeException('phase17_responder_failed');
                }

                return (int) $match[1];
            }
            usleep(10_000);
        }
        throw new RuntimeException('phase17_responder_failed');
    }

    private function child(string $mode, int $timeout = self::PROCESS_TIMEOUT_SECONDS): ProcessResult
    {
        return $this->closeCaptured($this->openCaptured([PHP_BINARY, __DIR__ . '/run.php', $mode]), $timeout);
    }

    /** @return array{ProcessResult,ProcessResult} */
    private function childPair(): array
    {
        $first = $this->openCaptured([PHP_BINARY, __DIR__ . '/run.php', '--child-success']);
        $second = $this->openCaptured([PHP_BINARY, __DIR__ . '/run.php', '--child-success']);

        return [$this->closeCaptured($first), $this->closeCaptured($second)];
    }

    /** @param list<string> $command
     * @return array{resource,TemporaryOpenAiResponsesWorkspace,string,string}
     */
    private function openCaptured(array $command): array
    {
        $workspace = new TemporaryOpenAiResponsesWorkspace($this->repositoryRoot);
        $stdoutPath = $workspace->write('stdout', '');
        $stderrPath = $workspace->write('stderr', '');
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['file', $stdoutPath, 'wb'], ['file', $stderrPath, 'wb']], $pipes, $this->repositoryRoot, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            $workspace->cleanup();
            throw new RuntimeException('phase17_process_failed');
        }
        fclose($pipes[0]);

        return [$process, $workspace, $stdoutPath, $stderrPath];
    }

    /** @param array{resource,TemporaryOpenAiResponsesWorkspace,string,string} $opened */
    private function closeCaptured(array $opened, int $timeout = self::PROCESS_TIMEOUT_SECONDS): ProcessResult
    {
        [$process, $workspace, $stdoutPath, $stderrPath] = $opened;
        $status = proc_get_status($process);
        $deadline = microtime(true) + $timeout;
        try {
            while ($status['running'] && microtime(true) < $deadline) {
                usleep(10_000);
                $status = proc_get_status($process);
            }
            $timedOut = $status['running'];
            if ($timedOut) {
                proc_terminate($process);
            }
            $closed = proc_close($process);
            $stdout = file_get_contents($stdoutPath, false, null, 0, self::STREAM_LIMIT + 1);
            $stderr = file_get_contents($stderrPath, false, null, 0, self::STREAM_LIMIT + 1);
            if ($timedOut || false === $stdout || false === $stderr || strlen($stdout) > self::STREAM_LIMIT || strlen($stderr) > self::STREAM_LIMIT) {
                throw new RuntimeException('phase17_process_failed');
            }
            $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;

            return new ProcessResult($exit, $stdout, $stderr);
        } finally {
            $workspace->cleanup();
        }
    }

    /** @param list<string> $command
     * @return array{resource,array<int,resource>}
     */
    private function open(array $command): array
    {
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $this->repositoryRoot, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new RuntimeException('phase17_process_failed');
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        return [$process, $pipes];
    }

    /** @param array{resource,array<int,resource>} $opened */
    private function close(array $opened, int $timeout = self::PROCESS_TIMEOUT_SECONDS): ProcessResult
    {
        [$process, $pipes] = $opened;
        $stdout = '';
        $stderr = '';
        $status = proc_get_status($process);
        $deadline = microtime(true) + $timeout;
        $overflow = false;
        $timedOut = false;
        try {
            while ($status['running']) {
                $this->appendBounded($stdout, $this->readAvailable($pipes[1]), $overflow);
                $this->appendBounded($stderr, $this->readAvailable($pipes[2]), $overflow);
                if (microtime(true) >= $deadline) {
                    $timedOut = true;
                    break;
                }
                usleep(10_000);
                $status = proc_get_status($process);
            }
            if (!$timedOut) {
                $this->appendBounded($stdout, $this->readAvailable($pipes[1]), $overflow);
                $this->appendBounded($stderr, $this->readAvailable($pipes[2]), $overflow);
            }
        } finally {
            foreach ([1, 2] as $pipe) {
                if (isset($pipes[$pipe]) && is_resource($pipes[$pipe])) {
                    fclose($pipes[$pipe]);
                }
            }
        }
        if ($timedOut && $status['running']) {
            proc_terminate($process);
        }
        $closed = proc_close($process);
        if ($timedOut || $overflow) {
            throw new RuntimeException('phase17_process_failed');
        }
        $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;

        return new ProcessResult($exit, $stdout, $stderr);
    }

    private function appendBounded(string &$buffer, string $chunk, bool &$overflow): void
    {
        $remaining = self::STREAM_LIMIT - strlen($buffer);
        if (strlen($chunk) > $remaining) {
            $overflow = true;
            $buffer .= substr($chunk, 0, max(0, $remaining));

            return;
        }
        $buffer .= $chunk;
    }

    /** @param resource $stream */
    private function readAvailable($stream): string
    {
        $bytes = stream_get_contents($stream, self::STREAM_LIMIT + 1);
        if (false === $bytes) {
            throw new RuntimeException('phase17_process_failed');
        }

        return $bytes;
    }

    private function repositoryFingerprint(): string
    {
        $result = $this->closeCaptured($this->openCaptured(['git', '-c', 'safe.directory=' . str_replace('\\', '/', $this->repositoryRoot), 'status', '--porcelain=v1', '--untracked-files=all']));
        $this->require(0 === $result->exitCode);

        return hash('sha256', $result->stdout);
    }

    private function directoryIdentity(string $root): string
    {
        $resolved = realpath($root);
        if (false === $resolved || !is_dir($resolved) || is_link($resolved)) {
            throw new RuntimeException('phase17_workspace_failed');
        }
        $root = rtrim(str_replace('\\', '/', $resolved), '/');
        $records = [];
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo || $item->isLink() || !$item->isFile()) {
                throw new RuntimeException('phase17_workspace_failed');
            }
            $path = str_replace('\\', '/', $item->getPathname());
            $bytes = file_get_contents($path);
            if (false === $bytes) {
                throw new RuntimeException('phase17_workspace_failed');
            }
            $records[] = substr($path, strlen($root) + 1) . '|' . strlen($bytes) . '|' . hash('sha256', $bytes);
        }
        sort($records, SORT_STRING);

        return hash('sha256', implode("\n", $records));
    }

    private function temporaryResidue(): string
    {
        $paths = glob(rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/') . '/kongono-phase17-*', GLOB_NOSORT);
        if (false === $paths) {
            throw new RuntimeException('phase17_acceptance_failed');
        }
        sort($paths, SORT_STRING);

        return hash('sha256', implode("\n", $paths));
    }

    private function isCanonicalSuccess(string $bytes): bool
    {
        if (!str_ends_with($bytes, "\n") || str_contains(substr($bytes, 0, -1), "\n")) {
            return false;
        }
        try {
            $decoded = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return false;
        }

        return is_array($decoded)
            && ['status', 'profile', 'case_count', 'cleanup'] === array_keys($decoded)
            && 'ok' === $decoded['status']
            && 'kongono-phase17-openai-responses' === $decoded['profile']
            && is_int($decoded['case_count'])
            && $decoded['case_count'] > 0
            && 'complete' === $decoded['cleanup']
            && self::successBytes($decoded['case_count']) === $bytes;
    }

    private function fails(callable $operation): bool
    {
        try {
            $operation();
        } catch (Throwable) {
            return true;
        }

        return false;
    }

    private function case(bool $passed): void
    {
        $this->require($passed);
        ++$this->caseCount;
    }

    private function require(bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException('phase17_acceptance_failed');
        }
    }

    private static function successBytes(int $caseCount): string
    {
        return json_encode(['status' => 'ok', 'profile' => 'kongono-phase17-openai-responses', 'case_count' => $caseCount, 'cleanup' => 'complete'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
    }

    private static function expectedRequest(string $profile): string
    {
        $base = '{"input":[{"content":[{"text":"synthetic input","type":"input_text"}],"role":"user","type":"message"}],"model":"openai-test-model"';

        return match ($profile) {
            'text' => $base . ',"store":false,"stream":false}',
            'stream' => $base . ',"store":false,"stream":true}',
            'structured' => $base . ',"store":false,"stream":false,"text":{"format":{"name":"answer_format","schema":' . self::SCHEMA . ',"strict":true,"type":"json_schema"}}}',
            'action' => $base . ',"parallel_tool_calls":false,"store":false,"stream":false,"tool_choice":"required","tools":[{"name":"record_answer","parameters":' . self::SCHEMA . ',"strict":true,"type":"function"}]}',
            default => throw new RuntimeException('phase17_profile_failed'),
        };
    }

    private static function textResponse(): string
    {
        return '{"id":"resp_synthetic_completed","incomplete_details":null,"model":"openai-test-model","object":"response","output":[{"content":[{"annotations":[],"text":"synthetic output","type":"output_text"}],"id":"msg_synthetic_completed","role":"assistant","status":"completed","type":"message"}],"status":"completed","usage":{"input_tokens":7,"output_tokens":5,"total_tokens":12}}';
    }

    private static function structuredResponse(): string
    {
        return '{"id":"resp_structured","incomplete_details":null,"model":"openai-test-model","object":"response","output":[{"content":[{"annotations":[],"text":"{\\"answer\\":\\"accepted\\"}","type":"output_text"}],"id":"msg_structured","role":"assistant","status":"completed","type":"message"}],"status":"completed","usage":{"input_tokens":7,"output_tokens":5,"total_tokens":12}}';
    }

    private static function actionResponse(): string
    {
        return '{"id":"resp_function","incomplete_details":null,"model":"openai-test-model","object":"response","output":[{"arguments":"{\\"answer\\":\\"accepted\\"}","call_id":"call_synthetic","id":"fc_synthetic","name":"record_answer","status":"completed","type":"function_call"}],"status":"completed","usage":{"input_tokens":7,"output_tokens":5,"total_tokens":12}}';
    }

    private static function streamResponse(): string
    {
        return <<<'SSE'
event: response.created
data: {"response":{"id":"resp_stream_completed","model":"openai-test-model","object":"response","output":[],"status":"in_progress"},"sequence_number":0,"type":"response.created"}

data: {"response":{"id":"resp_stream_completed","model":"openai-test-model","object":"response","output":[],"status":"in_progress"},"sequence_number":1,"type":"response.in_progress"}

event: response.output_item.added
data: {"item":{"content":[],"id":"msg_stream_completed","role":"assistant","status":"in_progress","type":"message"},"output_index":0,"sequence_number":2,"type":"response.output_item.added"}

data: {"content_index":0,"item_id":"msg_stream_completed","output_index":0,"part":{"annotations":[],"logprobs":[],"text":"","type":"output_text"},"sequence_number":3,"type":"response.content_part.added"}

data: {"content_index":0,"delta":"synthetic-","item_id":"msg_stream_completed","output_index":0,"sequence_number":4,"type":"response.output_text.delta"}

data: {"content_index":0,"delta":"stream-output","item_id":"msg_stream_completed","output_index":0,"sequence_number":5,"type":"response.output_text.delta"}

data: {"content_index":0,"item_id":"msg_stream_completed","logprobs":[],"output_index":0,"sequence_number":6,"text":"synthetic-stream-output","type":"response.output_text.done"}

data: {"content_index":0,"item_id":"msg_stream_completed","output_index":0,"part":{"annotations":[],"logprobs":[],"text":"synthetic-stream-output","type":"output_text"},"sequence_number":7,"type":"response.content_part.done"}

data: {"item":{"content":[{"annotations":[],"logprobs":[],"text":"synthetic-stream-output","type":"output_text"}],"id":"msg_stream_completed","role":"assistant","status":"completed","type":"message"},"output_index":0,"sequence_number":8,"type":"response.output_item.done"}

data: {"response":{"id":"resp_stream_completed","incomplete_details":null,"model":"openai-test-model","object":"response","output":[{"content":[{"annotations":[],"text":"synthetic-stream-output","type":"output_text"}],"id":"msg_stream_completed","role":"assistant","status":"completed","type":"message"}],"status":"completed","usage":{"input_tokens":7,"output_tokens":5,"total_tokens":12}},"sequence_number":9,"type":"response.completed"}

SSE . "\n";
    }
}
