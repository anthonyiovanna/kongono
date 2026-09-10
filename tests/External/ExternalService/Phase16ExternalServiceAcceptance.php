<?php

declare(strict_types=1);

namespace Kongono\Tests\External\ExternalService;

use Closure;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationResult;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationRunInput;
use Kongono\ExternalServiceApplication\Operation\ExternalServiceRequestFactory;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileFactory;
use Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplication;
use Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplicationState;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactPublisher;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactValidator;
use Kongono\ExternalServiceBindingBuild\Generated\GeneratedExternalServiceBindingsGenerator;
use Kongono\ExternalServiceBindingBuild\Generated\GeneratedExternalServiceBindingsPublisher;
use Kongono\ExternalServiceBindingBuild\Generated\GeneratedExternalServiceBindingsValidator;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSourceIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\ExternalServiceContract\Binding\ServiceBindingIdentity;
use Kongono\ExternalServiceContract\Binding\ServiceProfileIdentity;
use Kongono\ExternalServiceContract\ContractVersion;
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
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailure;
use Kongono\ExternalServiceContract\Failure\ExternalServiceFailureKind;
use Kongono\ExternalServiceContract\Lifecycle\ExternalServiceLifecycleState;
use Kongono\ExternalServiceContract\Operation\ExternalServiceOperationIdentity;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOperationResult;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOutcome;
use Kongono\ExternalServiceContract\Resolution\EndpointResolver;
use Kongono\ExternalServiceCredentialRuntime\Clock\CredentialMonotonicClock;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseCleanupException;
use Kongono\ExternalServiceCredentialRuntime\Exception\CredentialLeaseOperationException;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeRequest;
use Kongono\ExternalServiceCredentialRuntime\Execution\CredentialLeaseRuntimeResult;
use Kongono\ExternalServiceCredentialRuntime\Lifecycle\CredentialLeaseRuntimeState;
use Kongono\ExternalServiceCredentialRuntime\Outcome\CredentialLeaseRuntimeOutcome;
use Kongono\ExternalServiceCredentialRuntime\Runtime\CredentialLeaseRuntime;
use Kongono\ExternalServiceInspection\Derivation\ExternalServiceIntelligenceDeriver;
use Kongono\ExternalServiceInspection\Graph\ExternalServiceGraphEvidence;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspectionRequest;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspector;
use Kongono\ExternalServiceInspection\Projection\ExternalServiceObservationProjector;
use Kongono\ExternalServiceInspection\Projection\ExternalServiceSecurityAuditProjector;
use Kongono\ExternalServiceInspection\Role\ExternalServiceInspectionDeveloperRole;
use Kongono\ExternalServiceInspection\Verification\ExternalServiceIntelligenceVerdict;
use Kongono\ExternalServiceInspection\Verification\ExternalServiceIntelligenceVerifier;
use Kongono\ExternalServiceResolverSystem\Profile\AddressFamilyPreference;
use Kongono\ExternalServiceResolverSystem\Profile\SystemResolverProfile;
use Kongono\ExternalServiceResolverSystem\Resolver\SystemEndpointResolver;
use Kongono\Http\Message\Message\Response;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressApplication\Workflow\NetworkEgressApplicationBuildWorkflow;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Cancellation\CancellationEvidence;
use Kongono\NetworkEgressContract\Cancellation\CancellationState;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressOperationIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Lifecycle\EgressLifecycleState;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressContract\Outcome\EgressOperationResult;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Receipt\EgressReceipt;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
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
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;
use Kongono\Observability\ObservabilityContext;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Throwable;

final class AcceptanceAuthorityCurrentness implements EgressAuthorityCurrentness
{
    public int $calls = 0;

    public function isCurrent(EgressAuthorityReference $reference, CanonicalOrigin $origin): bool
    {
        unset($reference, $origin);
        ++$this->calls;

        return true;
    }
}

final class AcceptanceCancellation implements EgressCancellation
{
    public int $calls = 0;

    public function isCancellationRequested(): bool
    {
        ++$this->calls;

        return false;
    }
}

final class AcceptanceCredentialClock implements CredentialMonotonicClock
{
    private int $calls = 0;

    /** @param non-empty-list<int> $values */
    public function __construct(private readonly array $values = [0, 1, 2, 3]) {}

    public function milliseconds(): int
    {
        $position = min($this->calls, count($this->values) - 1);
        ++$this->calls;

        return $this->values[$position];
    }
}

final class AcceptanceEgressClock implements EgressMonotonicClock
{
    private int $value = 0;

    public function milliseconds(): int
    {
        return $this->value++;
    }
}

final class AcceptanceEndpointResolver implements EndpointResolver
{
    public int $calls = 0;
    public ?CanonicalOrigin $origin = null;

    public function resolve(CanonicalOrigin $origin): EgressResolutionResult
    {
        ++$this->calls;
        $this->origin = $origin;
        $address = new ResolvedAddress('127.0.0.1');

        return new EgressResolutionResult(new ResolutionEvidence([$address], $address), null);
    }
}

final class AcceptanceCredentialLease implements CredentialLease
{
    public int $materialCalls = 0;
    public int $retireCalls = 0;
    private CredentialLeaseState $state;

    public function __construct(
        private readonly CredentialReference $reference,
        private readonly CredentialPurpose $purpose,
        CredentialLeaseState $initialState = CredentialLeaseState::Acquired,
        private readonly ?Throwable $retirementFailure = null,
    ) {
        $this->state = $initialState;
    }

    public function identity(): CredentialLeaseIdentity
    {
        return new CredentialLeaseIdentity('lease.phase16.acceptance');
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
            $consumer->consume(Phase16ExternalServiceAcceptance::fixtureMaterial());
        } finally {
            $this->state = CredentialLeaseState::Acquired;
        }
    }

    public function retire(): void
    {
        ++$this->retireCalls;
        $this->state = CredentialLeaseState::Retiring;
        if ($this->retirementFailure instanceof Throwable) {
            throw $this->retirementFailure;
        }
        $this->state = CredentialLeaseState::Retired;
    }
}

final class AcceptanceCredentialSource implements CredentialLeaseSource
{
    public int $calls = 0;
    public ?CredentialLeaseRequest $request = null;

    public function __construct(private readonly CredentialLease|Throwable $result) {}

    public function acquire(CredentialLeaseRequest $request): CredentialLease
    {
        ++$this->calls;
        $this->request = $request;
        if ($this->result instanceof Throwable) {
            throw $this->result;
        }

        return $this->result;
    }
}

final class AcceptanceRequestFactory implements ExternalServiceRequestFactory
{
    public int $calls = 0;

    public function create(
        ServiceBinding $binding,
        ExternalServiceOperationIdentity $operationIdentity,
        #[\SensitiveParameter]
        string $credentialMaterial,
    ): RequestInterface {
        unset($operationIdentity);
        ++$this->calls;
        $origin = $binding->endpoint()->origin();
        $uri = 'http://' . $origin->host() . ':' . $origin->port() . '/phase16/proof?selected=address';

        return (new ServerRequest('GET', $uri))->withHeader('X-Kongono-Credential', $credentialMaterial);
    }
}

final class AcceptanceMaterialConsumer implements CredentialMaterialConsumer
{
    public int $calls = 0;

    public function __construct(private readonly ?Throwable $failure = null) {}

    public function consume(#[\SensitiveParameter] string $material): void
    {
        ++$this->calls;
        if (!hash_equals(Phase16ExternalServiceAcceptance::fixtureMaterial(), $material)) {
            throw new RuntimeException('phase16_credential_failed');
        }
        if ($this->failure instanceof Throwable) {
            throw $this->failure;
        }
    }
}

final class Phase16ExternalServiceAcceptance
{
    private const int STREAM_LIMIT = 1_048_576;
    private const int PROCESS_TIMEOUT_SECONDS = 20;
    private const string FAILURE = "phase16_acceptance_failed\n";
    private const string HOST = 'selected-address-service.invalid';
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
                $workspace = new TemporaryExternalServiceWorkspace($this->repositoryRoot);
                try {
                    throw new RuntimeException('private failure sentinel');
                } finally {
                    $workspace->cleanup();
                }
            }
            if (['--responder'] === $arguments) {
                return $this->respond($stdout);
            }
            if ([] !== $arguments) {
                throw new RuntimeException('invalid mode');
            }

            $before = $this->repositoryFingerprint();
            $residue = $this->temporaryResidue();
            $single = $this->child('--child-success');
            $failure = $this->child('--child-failure');
            $later = $this->child('--child-success');
            $sequential = $this->child('--child-success');
            [$concurrentA, $concurrentB] = $this->childPair();
            $this->require(0 === $single->exitCode && '' === $single->stderr);
            $this->require($this->isCanonicalSuccess($single->stdout));
            $this->require(0 !== $failure->exitCode && '' === $failure->stdout && self::FAILURE === $failure->stderr);
            $this->require(0 === $later->exitCode && $single->stdout === $later->stdout && '' === $later->stderr);
            $this->require(0 === $sequential->exitCode && $single->stdout === $sequential->stdout && '' === $sequential->stderr);
            $this->require(0 === $concurrentA->exitCode && 0 === $concurrentB->exitCode);
            $this->require($single->stdout === $concurrentA->stdout && $concurrentA->stdout === $concurrentB->stdout);
            $this->require('' === $concurrentA->stderr && '' === $concurrentB->stderr);
            $this->require($before === $this->repositoryFingerprint());
            $this->require($residue === $this->temporaryResidue());
            fwrite($stdout, $single->stdout);

            return 0;
        } catch (Throwable) {
            fwrite($stderr, self::FAILURE);

            return 1;
        }
    }

    public static function fixtureMaterial(): string
    {
        return hash('sha256', 'kongono-phase16-inert-fixture-seed');
    }

    /** @param resource $stdout */
    private function successChild($stdout): int
    {
        if (!extension_loaded('curl') || !extension_loaded('sockets')) {
            throw new RuntimeException('phase16_extension_failed');
        }
        $workspace = new TemporaryExternalServiceWorkspace($this->repositoryRoot);
        $responder = $this->open([PHP_BINARY, __DIR__ . '/run.php', '--responder']);
        try {
            $port = $this->responderPort($responder);
            $this->accept($workspace, $port);
            $response = $this->close($responder);
            $responder = null;
            $this->require(0 === $response->exitCode && '' === $response->stdout && '' === $response->stderr);
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

    private function accept(TemporaryExternalServiceWorkspace $workspace, int $port): void
    {
        $capabilities = new TransportCapabilitySet([TransportCapability::Http11]);
        $origin = new CanonicalOrigin(OriginScheme::Http, self::HOST, $port);
        $sourceIdentity = new CredentialSourceIdentity('credentials.phase16.acceptance');
        $reference = new CredentialReference($sourceIdentity, 'fixture/phase16');
        $binding = new ServiceBinding(
            new ServiceBindingIdentity('binding.phase16.acceptance'),
            new ServiceProfileIdentity('profile.phase16.acceptance'),
            new ServiceEndpoint($origin, new EndpointBasePath('/phase16')),
            $reference,
            $capabilities,
        );
        $source = new ExternalServiceBindingSource(new ExternalServiceBindingSourceIdentity('bindings.phase16.acceptance'), [$binding]);

        $this->case($binding->identity()->equals(new ServiceBindingIdentity('binding.phase16.acceptance')));
        $this->case($binding->profile()->equals(new ServiceProfileIdentity('profile.phase16.acceptance')));
        $this->case($binding->endpoint()->origin()->equals($origin));
        $this->case('/phase16' === $binding->endpoint()->basePath()->value());
        $this->case($binding->credentialReference()->equals($reference));
        $this->case($binding->requiredCapabilities()->contains(TransportCapability::Http11));

        $builder = new ExternalServiceBindingArtifactBuilder();
        $artifact = $builder->build($source);
        $validator = new ExternalServiceBindingArtifactValidator();
        $artifactData = json_decode($artifact->canonicalBytes(), true, flags: JSON_THROW_ON_ERROR);
        $this->case(is_array($artifactData) && ContractVersion::CURRENT === $artifactData['contract_version']);
        $this->case($artifact->canonicalBytes() === $builder->build($source)->canonicalBytes());
        $this->case(hash('sha256', $artifact->canonicalBytes()) === $artifact->artifactSha256());
        $this->case($artifact->artifactSha256() === $validator->validateStructure($artifact->canonicalBytes())->artifactSha256());
        $this->case($artifact->sourceSha256() === $validator->validateCurrent($source, $artifact->canonicalBytes())->sourceSha256());
        $this->case($this->fails(fn() => $validator->validateStructure(substr($artifact->canonicalBytes(), 0, -1))));
        $staleSource = new ExternalServiceBindingSource(new ExternalServiceBindingSourceIdentity('bindings.phase16.stale'), [$binding]);
        $this->case($this->fails(fn() => $validator->validateCurrent($staleSource, $artifact->canonicalBytes())));

        $artifactDirectory = $workspace->directory('binding-artifact');
        $artifactPublisher = new ExternalServiceBindingArtifactPublisher();
        $artifactPath = $artifactPublisher->publish($artifact, $artifactDirectory);
        $this->case(is_file($artifactPath));
        $this->case($artifactPath === $artifactPublisher->publish($artifact, $artifactDirectory));
        $this->case($this->fails(fn() => $artifactPublisher->publish($artifact, 'relative')));

        $generator = new GeneratedExternalServiceBindingsGenerator();
        $generated = $generator->generate($artifact);
        $generatedValidator = new GeneratedExternalServiceBindingsValidator();
        $this->case($generated->canonicalBytes() === $generator->generate($artifact)->canonicalBytes());
        $this->case($generated->generatedPhpSha256() === hash('sha256', $generated->canonicalBytes()));
        $this->case($generated->generatedPhpSha256() === $generatedValidator->validateStructure($generated->canonicalBytes())->generatedPhpSha256());
        $this->case($generated->generatedPhpSha256() === $generatedValidator->validateCurrent($source, $artifact, $generated->canonicalBytes())->generatedPhpSha256());
        $this->case($this->fails(fn() => $generatedValidator->validateStructure($generated->canonicalBytes() . '<?php')));
        $generatedDirectory = $workspace->directory('generated-binding');
        $generatedPublisher = new GeneratedExternalServiceBindingsPublisher();
        $generatedPath = $generatedPublisher->publish($generated, $generatedDirectory);
        $this->case(is_file($generatedPath));
        $this->case($generatedPath === $generatedPublisher->publish($generated, $generatedDirectory));
        $this->case(!class_exists('Kongono\\Generated\\ExternalServiceBindings', false));

        $systemResolver = new SystemEndpointResolver(new SystemResolverProfile(AddressFamilyPreference::Ipv4First, 8));
        $numericOrigin = new CanonicalOrigin(OriginScheme::Http, '127.0.0.1', $port);
        $systemResolution = $systemResolver->resolve($numericOrigin);
        $this->case($systemResolution->succeeded());
        $this->case('127.0.0.1' === $systemResolution->evidence()?->selectedAddress()->value());
        $this->case(1 === count($systemResolution->evidence()?->addresses() ?? []));
        $this->case($systemResolution->evidence()?->selectedAddress()->equals(new ResolvedAddress('127.0.0.1')) ?? false);

        $budget = new EgressBudget(1, 0, 1, 65_536, 65_536, 32, 8_192, 65_536, 8_192, 5_000, 5_000, 10_000);
        $policy = new EgressPolicySource(
            new EgressPolicyIdentity('policy.phase16.acceptance'),
            [$origin],
            AddressScope::ExplicitPrivateAllowed,
            TransportSecurity::ExplicitHttpAllowed,
            RedirectMode::Deny,
            ProxyMode::Deny,
            CookieMode::Deny,
            CredentialMode::OperationScoped,
            $budget,
            $capabilities,
        );
        $policyArtifact = (new EgressPolicyArtifactBuilder())->build($policy);
        $networkProfile = (new NetworkEgressApplicationProfileFactory())->create($policy, $policyArtifact->canonicalBytes());
        $profile = (new ExternalServiceApplicationProfileFactory())->create($source, $artifact->canonicalBytes(), $binding->identity(), $networkProfile);
        $applicationRoot = $workspace->directory('network-application');
        $this->require(mkdir($applicationRoot . '/generated', 0700));
        $build = (new NetworkEgressApplicationBuildWorkflow())->build(
            $applicationRoot,
            'generated',
            $policy,
            $policyArtifact->canonicalBytes(),
        );
        $resolver = new AcceptanceEndpointResolver();
        $transport = new CurlEgressTransport(new CurlTransportProfile(new CurlTlsTrust(self::certificateShape()), true));
        $purpose = new CredentialPurpose('invoke.phase16.acceptance');
        $lease = new AcceptanceCredentialLease($reference, $purpose);
        $credentialSource = new AcceptanceCredentialSource($lease);
        $application = new ExternalServiceApplication(
            $profile,
            $build->loadedWiring(),
            $resolver,
            $transport,
            $sourceIdentity,
            $credentialSource,
        );
        $requestFactory = new AcceptanceRequestFactory();
        $authority = new AcceptanceAuthorityCurrentness();
        $input = new ExternalServiceApplicationRunInput(
            new ExternalServiceOperationIdentity('operation.phase16.acceptance'),
            $purpose,
            5_000,
            $requestFactory,
            new EgressReceiptIdentity('receipt.phase16.acceptance'),
            new EgressAttemptIdentity('attempt.phase16.acceptance'),
            new EgressAuthorityReference('authority.phase16.acceptance', 1),
            $authority,
            new AcceptanceCancellation(),
            new AcceptanceCredentialClock(),
            new AcceptanceEgressClock(),
        );
        $result = $application->run($input);
        $credentialResult = $result->credentialResult();
        $egressResult = $result->egressResult();
        $response = $egressResult->operationResult()->response();
        $this->case(ExternalServiceOutcome::Succeeded === $result->operationResult()->outcome());
        $this->case(ExternalServiceLifecycleState::Retired === $result->operationResult()->lifecycleState());
        $this->case(CredentialLeaseRuntimeOutcome::Succeeded === $credentialResult->outcome());
        $this->case(CredentialLeaseRuntimeState::Retired === $credentialResult->state());
        $this->case(1 === $credentialResult->acquisitionAttemptCount());
        $this->case(1 === $credentialResult->materialAccessAttemptCount());
        $this->case(1 === $credentialResult->retirementAttemptCount());
        $this->case($credentialResult->cleanupComplete());
        $this->case(1 === $credentialSource->calls && 1 === $lease->materialCalls && 1 === $lease->retireCalls);
        $this->case(1 === $requestFactory->calls && 1 === $resolver->calls);
        $this->case($resolver->origin?->equals($origin) ?? false);
        $this->case($authority->calls >= 2);
        $this->case(EgressOutcome::Succeeded === $egressResult->operationResult()->receipt()->outcome());
        $this->case(NegotiatedProtocol::Http11 === $egressResult->operationResult()->receipt()->negotiatedProtocol());
        $this->case(EgressTransferState::ResponseReceived === $egressResult->operationResult()->receipt()->transfer()->state());
        $this->case(1 === $egressResult->operationResult()->receipt()->connectionAttemptCount());
        $this->case($egressResult->operationResult()->receipt()->transfer()->requestBytes() > 0);
        $this->case($egressResult->operationResult()->receipt()->transfer()->responseBytes() > 0);
        $this->case(null !== $response && 200 === $response->getStatusCode());
        $this->case(null !== $response && 'phase16-loopback-proof' === (string) $response->getBody());
        $this->case(ExternalServiceApplicationState::Retired === $application->state() && !$application->hasActiveOperation());
        $this->case($build->loadedWiring()->started());
        $this->case($this->fails(fn() => $application->run($input)));

        $this->inspect($profile, $result);
        $this->credentialFailures($reference, $purpose, $sourceIdentity);
        $this->outcomeEvidence($profile);
        $this->case($this->fails(fn() => (new ExternalServiceApplicationProfileFactory())->create($staleSource, $artifact->canonicalBytes(), $binding->identity(), $networkProfile)));
        $unsupported = new ServiceBinding(
            new ServiceBindingIdentity('binding.phase16.unsupported'),
            new ServiceProfileIdentity('profile.phase16.unsupported'),
            new ServiceEndpoint($origin, new EndpointBasePath('/phase16')),
            $reference,
            new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::RequestStreaming]),
        );
        $unsupportedSource = new ExternalServiceBindingSource(new ExternalServiceBindingSourceIdentity('bindings.phase16.unsupported'), [$unsupported]);
        $unsupportedBytes = $builder->build($unsupportedSource)->canonicalBytes();
        $this->case($this->fails(fn() => (new ExternalServiceApplicationProfileFactory())->create($unsupportedSource, $unsupportedBytes, $unsupported->identity(), $networkProfile)));
    }

    private function inspect(ExternalServiceApplicationProfile $profile, ExternalServiceApplicationResult $result): void
    {
        $inspector = new ExternalServiceInspector();
        $inspection = $inspector->inspect(new ExternalServiceInspectionRequest($profile, $result));
        $again = $inspector->inspect(new ExternalServiceInspectionRequest($profile, $result));
        $this->case($inspection->canonicalBytes() === $again->canonicalBytes());
        $this->case($inspection->resultSupplied());
        $this->case(ExternalServiceOutcome::Succeeded === $inspection->serviceOutcome());
        $this->case(EgressOutcome::Succeeded === $inspection->egressOutcome());
        $this->case($inspection->credentialCleanupComplete());
        $this->case(1 === $inspection->resultCounts()['credential_acquisition_attempts']);
        $this->case(1 === $inspection->resultCounts()['credential_material_access_attempts']);
        $this->case(1 === $inspection->resultCounts()['credential_retirement_attempts']);
        $limitations = $inspection->toArray()['limitations'];
        $this->case(is_array($limitations) && 'not-performed' === $limitations['generated_php_loading']);
        $this->case(is_array($limitations) && 'not-established' === $limitations['network_provider_effect']);
        foreach (ExternalServiceInspectionDeveloperRole::cases() as $role) {
            $view = (new ExternalServiceIntelligenceDeriver())->derive($inspection, $role);
            $verification = (new ExternalServiceIntelligenceVerifier())->verify($inspection, $view);
            $this->case(ExternalServiceIntelligenceVerdict::Pass === $verification->verdict());
            $this->case(ExternalServiceGraphEvidence::RELATIONSHIP === $view->graphEvidence()->relationship());
            $this->case(!$view->graphEvidence()->supplied());
            $this->case('not-performed' === $view->roleExecution());
            $this->case('not-performed' === $view->credentialAccess());
            $this->case('not-performed' === $view->networkAccess());
            $this->case('evidence-only' === $view->authority());
        }
        $context = ObservabilityContext::uncorrelated();
        $observation = (new ExternalServiceObservationProjector())->project($inspection, $context);
        $audit = (new ExternalServiceSecurityAuditProjector())->project($inspection, $context);
        $this->case('kongono.external-service.inspection' === $observation->identity()->name());
        $this->case('kongono.external-service.inspection.security' === $audit->definition()->identity()->name());

        $overBudget = new EgressRuntimeResult(
            $result->egressResult()->operationResult(),
            $profile->networkEgressProfile()->source()->budget()->totalTimeoutMilliseconds() + 1,
            $result->egressResult()->resolutionCandidateCount(),
            $result->egressResult()->progressEmissionCount(),
            $result->egressResult()->peakBufferedBytes(),
            $result->egressResult()->finalRetainedEvidenceBytes(),
        );
        $overBudgetResult = new ExternalServiceApplicationResult($result->operationResult(), $result->credentialResult(), $overBudget);
        $this->case($this->fails(fn() => $inspector->inspect(new ExternalServiceInspectionRequest($profile, $overBudgetResult))));
    }

    private function credentialFailures(CredentialReference $reference, CredentialPurpose $purpose, CredentialSourceIdentity $sourceIdentity): void
    {
        $operation = new ExternalServiceOperationIdentity('operation.phase16.credential');
        $leaseRequest = new CredentialLeaseRequest($operation, $reference, $purpose, 10);

        $sourceFailure = new RuntimeException('private-source-failure');
        $source = new AcceptanceCredentialSource($sourceFailure);
        $failure = $this->capture(fn() => (new CredentialLeaseRuntime())->run(new CredentialLeaseRuntimeRequest(
            $leaseRequest,
            $sourceIdentity,
            $source,
            new AcceptanceMaterialConsumer(),
            new AcceptanceCredentialClock([0]),
        )));
        $this->case($failure instanceof CredentialLeaseOperationException);
        $this->case($failure instanceof CredentialLeaseOperationException && CredentialLeaseRuntimeOutcome::FailedBeforeAcquisition === $failure->terminalResult()->outcome());

        $protocolLease = new AcceptanceCredentialLease($reference, $purpose, CredentialLeaseState::InUse);
        $protocolFailure = $this->credentialFailure($leaseRequest, $sourceIdentity, $protocolLease, new AcceptanceMaterialConsumer(), [0]);
        $this->case($protocolFailure instanceof CredentialLeaseOperationException);
        $this->case(1 === $protocolLease->retireCalls && 0 === $protocolLease->materialCalls);

        $consumerLease = new AcceptanceCredentialLease($reference, $purpose);
        $consumerFailure = $this->credentialFailure(
            $leaseRequest,
            $sourceIdentity,
            $consumerLease,
            new AcceptanceMaterialConsumer(new RuntimeException('private-consumer-failure')),
            [0, 0],
        );
        $this->case($consumerFailure instanceof CredentialLeaseOperationException);
        $this->case(1 === $consumerLease->materialCalls && 1 === $consumerLease->retireCalls);
        $this->case($consumerFailure instanceof CredentialLeaseOperationException && CredentialLeaseRuntimeOutcome::MaterialUseUncertain === $consumerFailure->terminalResult()->outcome());

        $lifetimeLease = new AcceptanceCredentialLease($reference, $purpose);
        $lifetimeFailure = $this->credentialFailure($leaseRequest, $sourceIdentity, $lifetimeLease, new AcceptanceMaterialConsumer(), [0, 11]);
        $this->case($lifetimeFailure instanceof CredentialLeaseOperationException);
        $this->case(0 === $lifetimeLease->materialCalls && 1 === $lifetimeLease->retireCalls);

        $retirementLease = new AcceptanceCredentialLease($reference, $purpose, retirementFailure: new RuntimeException('private-retirement-failure'));
        $retirementFailure = $this->credentialFailure($leaseRequest, $sourceIdentity, $retirementLease, new AcceptanceMaterialConsumer(), [0, 0, 0]);
        $this->case($retirementFailure instanceof CredentialLeaseCleanupException);
        $this->case(1 === $retirementLease->materialCalls && 1 === $retirementLease->retireCalls);
        $this->case(!str_contains((string) $sourceFailure, self::fixtureMaterial()));
        $this->case(!str_contains((string) $consumerFailure, self::fixtureMaterial()));
        $this->case(!str_contains((string) $retirementFailure, self::fixtureMaterial()));

        $successLease = new AcceptanceCredentialLease($reference, $purpose);
        $success = (new CredentialLeaseRuntime())->run(new CredentialLeaseRuntimeRequest(
            $leaseRequest,
            $sourceIdentity,
            new AcceptanceCredentialSource($successLease),
            new AcceptanceMaterialConsumer(),
            new AcceptanceCredentialClock(),
        ));
        $this->case(CredentialLeaseRuntimeOutcome::Succeeded === $success->outcome());
        $this->case($success->cleanupComplete() && 1 === $successLease->retireCalls);
    }

    /** @param non-empty-list<int> $clock */
    private function credentialFailure(
        CredentialLeaseRequest $leaseRequest,
        CredentialSourceIdentity $sourceIdentity,
        AcceptanceCredentialLease $lease,
        AcceptanceMaterialConsumer $consumer,
        array $clock,
    ): Throwable {
        return $this->capture(fn() => (new CredentialLeaseRuntime())->run(new CredentialLeaseRuntimeRequest(
            $leaseRequest,
            $sourceIdentity,
            new AcceptanceCredentialSource($lease),
            $consumer,
            new AcceptanceCredentialClock($clock),
        )));
    }

    private function outcomeEvidence(ExternalServiceApplicationProfile $profile): void
    {
        $inspector = new ExternalServiceInspector();
        foreach (EgressOutcome::cases() as $outcome) {
            $result = $this->detachedResult($profile, $outcome);
            $inspection = $inspector->inspect(new ExternalServiceInspectionRequest($profile, $result));
            $this->case($outcome === $inspection->egressOutcome());
            $this->case($inspection->credentialCleanupComplete());
            $this->case(
                in_array($outcome, [EgressOutcome::CancelledAfterPossibleEffect, EgressOutcome::UncertainEffect], true)
                === $inspection->possibleEffectUncertainty(),
            );
        }
    }

    private function detachedResult(ExternalServiceApplicationProfile $profile, EgressOutcome $outcome): ExternalServiceApplicationResult
    {
        $attempt = match ($outcome) {
            EgressOutcome::RejectedBeforeAttempt, EgressOutcome::FailedBeforeEffect, EgressOutcome::CancelledBeforeEffect => null,
            default => new EgressAttemptIdentity('attempt.phase16.detached'),
        };
        $lifecycle = match ($outcome) {
            EgressOutcome::RejectedBeforeAttempt, EgressOutcome::FailedBeforeEffect => EgressLifecycleState::Failed,
            EgressOutcome::CancelledBeforeEffect, EgressOutcome::CancelledAfterPossibleEffect => EgressLifecycleState::Cancelled,
            EgressOutcome::Succeeded => EgressLifecycleState::Succeeded,
            EgressOutcome::UncertainEffect => EgressLifecycleState::Uncertain,
        };
        $transfer = match ($outcome) {
            EgressOutcome::Succeeded => new EgressTransferEvidence(EgressTransferState::ResponseReceived, 9, 11),
            EgressOutcome::CancelledAfterPossibleEffect => new EgressTransferEvidence(EgressTransferState::RequestSent, 9, 0),
            EgressOutcome::UncertainEffect => new EgressTransferEvidence(EgressTransferState::RequestPartiallySent, 4, 0),
            default => new EgressTransferEvidence(EgressTransferState::None, 0, 0),
        };
        $cancellation = match ($outcome) {
            EgressOutcome::CancelledBeforeEffect => new CancellationEvidence(CancellationState::RequestedBeforeAttempt, 1),
            EgressOutcome::CancelledAfterPossibleEffect => new CancellationEvidence(CancellationState::RequestedAfterPossibleEffect, 2),
            default => new CancellationEvidence(CancellationState::NotRequested, 0),
        };
        $failureKind = match ($outcome) {
            EgressOutcome::Succeeded => null,
            EgressOutcome::RejectedBeforeAttempt => EgressFailureKind::PolicyDenied,
            EgressOutcome::CancelledBeforeEffect, EgressOutcome::CancelledAfterPossibleEffect => EgressFailureKind::Cancellation,
            EgressOutcome::UncertainEffect => EgressFailureKind::UncertainEffect,
            EgressOutcome::FailedBeforeEffect => EgressFailureKind::Connection,
        };
        $identity = new EgressOperationIdentity('operation.phase16.detached');
        $receipt = new EgressReceipt(
            new EgressReceiptIdentity('receipt.phase16.detached'),
            $identity,
            $attempt,
            $lifecycle,
            $outcome,
            null === $attempt ? 0 : 1,
            $transfer,
            $cancellation,
            null === $attempt ? NegotiatedProtocol::Unknown : NegotiatedProtocol::Http11,
            new TransportCapabilitySet([TransportCapability::Http11]),
        );
        $egressFailure = null === $failureKind ? null : new EgressFailure($failureKind, $failureKind->value);
        $egress = new EgressRuntimeResult(
            new EgressOperationResult($receipt, EgressOutcome::Succeeded === $outcome ? new Response() : null, $egressFailure),
            4,
            null === $attempt ? 0 : 1,
            1,
            64,
            48,
        );
        $externalOutcome = ExternalServiceOutcome::from($outcome->value);
        $externalFailureKind = match ($failureKind) {
            null => null,
            EgressFailureKind::PolicyDenied => ExternalServiceFailureKind::Policy,
            EgressFailureKind::Cancellation => ExternalServiceFailureKind::Cancelled,
            default => ExternalServiceFailureKind::Transport,
        };
        $operation = new ExternalServiceOperationResult(
            new ExternalServiceOperationIdentity($identity->value()),
            $profile->binding()->identity(),
            in_array($externalOutcome, [ExternalServiceOutcome::CancelledAfterPossibleEffect, ExternalServiceOutcome::UncertainEffect], true)
                ? ExternalServiceLifecycleState::Failed
                : ExternalServiceLifecycleState::Retired,
            $externalOutcome,
            null === $externalFailureKind || null === $failureKind
                ? null
                : new ExternalServiceFailure($externalFailureKind, $failureKind->value),
        );
        $credential = new CredentialLeaseRuntimeResult(
            CredentialLeaseRuntimeOutcome::Succeeded,
            CredentialLeaseRuntimeState::Retired,
            null,
            3,
            1,
            1,
            1,
            true,
        );

        return new ExternalServiceApplicationResult($operation, $credential, $egress);
    }

    /** @param resource $stdout */
    private function respond($stdout): int
    {
        $server = @stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
        unset($errorCode, $errorMessage);
        if (!is_resource($server)) {
            throw new RuntimeException('phase16_responder_failed');
        }
        $client = null;
        try {
            $name = stream_socket_get_name($server, false);
            if (false === $name || 1 !== preg_match('/\A127\.0\.0\.1:([1-9][0-9]{0,4})\z/D', $name, $match) || (int) $match[1] > 65_535) {
                throw new RuntimeException('phase16_responder_failed');
            }
            $port = (int) $match[1];
            fwrite($stdout, 'READY:' . $port . "\n");
            fflush($stdout);
            $client = @stream_socket_accept($server, 5);
            if (!is_resource($client)) {
                throw new RuntimeException('phase16_responder_failed');
            }
            stream_set_timeout($client, 5);
            $request = '';
            while (!str_contains($request, "\r\n\r\n")) {
                $chunk = fread($client, 4_096);
                if (false === $chunk || '' === $chunk) {
                    $metadata = stream_get_meta_data($client);
                    if ($metadata['timed_out'] || feof($client)) {
                        throw new RuntimeException('phase16_responder_failed');
                    }
                    continue;
                }
                $request .= $chunk;
                if (strlen($request) > 65_536) {
                    throw new RuntimeException('phase16_responder_failed');
                }
            }
            [$head, $tail] = explode("\r\n\r\n", $request, 2);
            if ('' !== $tail) {
                throw new RuntimeException('phase16_responder_failed');
            }
            $lines = explode("\r\n", $head);
            if ('GET /phase16/proof?selected=address HTTP/1.1' !== array_shift($lines)) {
                throw new RuntimeException('phase16_responder_failed');
            }
            $hosts = [];
            $credentials = [];
            foreach ($lines as $line) {
                $position = strpos($line, ':');
                if (false === $position) {
                    throw new RuntimeException('phase16_responder_failed');
                }
                $header = strtolower(substr($line, 0, $position));
                $value = trim(substr($line, $position + 1));
                if ('host' === $header) {
                    $hosts[] = $value;
                }
                if ('x-kongono-credential' === $header) {
                    $credentials[] = $value;
                }
                if (in_array($header, ['content-length', 'transfer-encoding'], true)) {
                    throw new RuntimeException('phase16_responder_failed');
                }
            }
            if ([self::HOST . ':' . $port] !== $hosts || [self::fixtureMaterial()] !== $credentials) {
                throw new RuntimeException('phase16_responder_failed');
            }
            $body = 'phase16-loopback-proof';
            $response = $this->loopbackResponse($body);
            if (strlen($response) > 4_096) {
                throw new RuntimeException('phase16_responder_failed');
            }
            $written = 0;
            while ($written < strlen($response)) {
                $count = fwrite($client, substr($response, $written));
                if (false === $count || 0 === $count) {
                    throw new RuntimeException('phase16_responder_failed');
                }
                $written += $count;
            }
            fflush($client);

            return 0;
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
            fclose($server);
        }
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
                    throw new RuntimeException('phase16_responder_failed');
                }

                return (int) $match[1];
            }
            usleep(10_000);
        }
        throw new RuntimeException('phase16_responder_failed');
    }

    private function child(string $mode): ProcessResult
    {
        return $this->close($this->open([PHP_BINARY, __DIR__ . '/run.php', $mode]));
    }

    /** @return array{ProcessResult,ProcessResult} */
    private function childPair(): array
    {
        $first = $this->open([PHP_BINARY, __DIR__ . '/run.php', '--child-success']);
        $second = $this->open([PHP_BINARY, __DIR__ . '/run.php', '--child-success']);

        return [$this->close($first), $this->close($second)];
    }

    /** @param list<string> $command
     * @return array{resource,array<int,resource>}
     */
    private function open(array $command): array
    {
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $this->repositoryRoot, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new RuntimeException('phase16_process_failed');
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        return [$process, $pipes];
    }

    /** @param array{resource,array<int,resource>} $opened */
    private function close(array $opened): ProcessResult
    {
        [$process, $pipes] = $opened;
        $stdout = '';
        $stderr = '';
        $deadline = microtime(true) + self::PROCESS_TIMEOUT_SECONDS;
        $status = proc_get_status($process);
        while ($status['running']) {
            $stdout .= $this->readAvailable($pipes[1]);
            $stderr .= $this->readAvailable($pipes[2]);
            $this->require(strlen($stdout) <= self::STREAM_LIMIT && strlen($stderr) <= self::STREAM_LIMIT);
            if (microtime(true) >= $deadline) {
                proc_terminate($process);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                throw new RuntimeException('phase16_process_failed');
            }
            usleep(10_000);
            $status = proc_get_status($process);
        }
        $stdout .= $this->readAvailable($pipes[1]);
        $stderr .= $this->readAvailable($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $closed = proc_close($process);
        $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;
        $this->require(strlen($stdout) <= self::STREAM_LIMIT && strlen($stderr) <= self::STREAM_LIMIT);

        return new ProcessResult($exit, $stdout, $stderr);
    }

    /** @param resource $stream */
    private function readAvailable($stream): string
    {
        $bytes = stream_get_contents($stream, self::STREAM_LIMIT + 1);
        if (false === $bytes) {
            throw new RuntimeException('phase16_process_failed');
        }

        return $bytes;
    }

    private function repositoryFingerprint(): string
    {
        $result = $this->close($this->open(['git', '-c', 'safe.directory=' . str_replace('\\', '/', $this->repositoryRoot), 'status', '--porcelain=v1', '--untracked-files=all']));
        $this->require(0 === $result->exitCode);

        return hash('sha256', $result->stdout);
    }

    private function temporaryResidue(): string
    {
        $paths = glob(rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/') . '/kongono-phase16-*', GLOB_NOSORT);
        if (false === $paths) {
            throw new RuntimeException('phase16_acceptance_failed');
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
            && 'kongono-phase16-external-service' === $decoded['profile']
            && is_int($decoded['case_count'])
            && $decoded['case_count'] > 0
            && 'complete' === $decoded['cleanup']
            && self::successBytes($decoded['case_count']) === $bytes;
    }

    private function loopbackResponse(string $body): string
    {
        return sprintf("HTTP/1.1 200 OK\r\nContent-Length: %d\r\nContent-Type: text/plain\r\nConnection: close\r\n\r\n%s", strlen($body), $body);
    }

    private function capture(Closure $operation): Throwable
    {
        try {
            $operation();
        } catch (Throwable $failure) {
            return $failure;
        }

        throw new RuntimeException('phase16_expected_failure_missing');
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
            throw new RuntimeException('phase16_acceptance_failed');
        }
    }

    private static function successBytes(int $caseCount): string
    {
        return json_encode(['status' => 'ok', 'profile' => 'kongono-phase16-external-service', 'case_count' => $caseCount, 'cleanup' => 'complete'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
    }

    private static function certificateShape(): string
    {
        return "-----BEGIN CERTIFICATE-----\nAA==\n-----END CERTIFICATE-----\n";
    }
}
