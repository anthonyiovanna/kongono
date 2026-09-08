<?php

declare(strict_types=1);

namespace Kongono\Tests\External\NetworkEgress;

use Kongono\Http\Message\Message\Response;
use Kongono\Http\Message\Message\ServerRequest;
use Kongono\NetworkEgressApplication\Generated\NetworkEgressWiringPublisher;
use Kongono\NetworkEgressApplication\Generated\NetworkEgressWiringValidator;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplicationRunInput;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplicationState;
use Kongono\NetworkEgressApplication\Workflow\NetworkEgressApplicationBuildWorkflow;
use Kongono\NetworkEgressContract\Authority\EgressAuthorityReference;
use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Capability\TransportCapability;
use Kongono\NetworkEgressContract\Capability\TransportCapabilitySet;
use Kongono\NetworkEgressContract\Failure\EgressFailure;
use Kongono\NetworkEgressContract\Failure\EgressFailureKind;
use Kongono\NetworkEgressContract\Identity\EgressAttemptIdentity;
use Kongono\NetworkEgressContract\Identity\EgressOperationIdentity;
use Kongono\NetworkEgressContract\Identity\EgressReceiptIdentity;
use Kongono\NetworkEgressContract\Lifecycle\EgressLifecycleState;
use Kongono\NetworkEgressContract\Operation\EgressOperation;
use Kongono\NetworkEgressContract\Origin\CanonicalOrigin;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Kongono\NetworkEgressContract\Outcome\EgressOutcome;
use Kongono\NetworkEgressContract\Transfer\EgressTransferEvidence;
use Kongono\NetworkEgressContract\Transfer\EgressTransferState;
use Kongono\NetworkEgressContract\Transport\NegotiatedProtocol;
use Kongono\NetworkEgressCurl\Profile\CurlTlsTrust;
use Kongono\NetworkEgressCurl\Profile\CurlTransportProfile;
use Kongono\NetworkEgressCurl\Transport\CurlEgressTransport;
use Kongono\NetworkEgressInspection\Derivation\NetworkEgressIntelligenceDeriver;
use Kongono\NetworkEgressInspection\Graph\NetworkEgressGraphEvidence;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspectionRequest;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspector;
use Kongono\NetworkEgressInspection\Projection\NetworkEgressObservationProjector;
use Kongono\NetworkEgressInspection\Projection\NetworkEgressSecurityAuditProjector;
use Kongono\NetworkEgressInspection\Role\NetworkEgressInspectionDeveloperRole;
use Kongono\NetworkEgressInspection\Verification\NetworkEgressIntelligenceVerdict;
use Kongono\NetworkEgressInspection\Verification\NetworkEgressIntelligenceVerifier;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactBuilder;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactPublisher;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactValidator;
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
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeRequest;
use Kongono\NetworkEgressRuntime\Execution\EgressRuntimeResult;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolutionResult;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;
use Kongono\NetworkEgressRuntime\Runtime\NetworkEgressRuntime;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportEmission;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportRequest;
use Kongono\NetworkEgressRuntime\Transport\EgressTransportSession;
use Kongono\Observability\ObservabilityContext;
use RuntimeException;
use Throwable;

final class AcceptanceAuthorityCurrentness implements EgressAuthorityCurrentness
{
    public int $calls = 0;

    /** @param non-empty-list<bool> $answers */
    public function __construct(private array $answers = [true, true]) {}

    public function isCurrent(EgressAuthorityReference $reference, CanonicalOrigin $origin): bool
    {
        unset($reference, $origin);
        $answer = $this->answers[min($this->calls, count($this->answers) - 1)];
        ++$this->calls;

        return $answer;
    }
}

final class AcceptanceCancellation implements EgressCancellation
{
    public int $calls = 0;

    public function __construct(public bool $requested = false) {}

    public function isCancellationRequested(): bool
    {
        ++$this->calls;

        return $this->requested;
    }
}

final class AcceptanceClock implements EgressMonotonicClock
{
    private int $value = 0;

    public function milliseconds(): int
    {
        return $this->value++;
    }
}

final class AcceptanceResolver implements EgressResolver
{
    public int $calls = 0;
    public ?CanonicalOrigin $origin = null;

    public function __construct(private readonly EgressResolutionResult $result) {}

    public function resolve(CanonicalOrigin $origin): EgressResolutionResult
    {
        ++$this->calls;
        $this->origin = $origin;

        return $this->result;
    }
}

final class AcceptanceTransportSession implements EgressTransportSession
{
    public int $nextCalls = 0;
    public int $closeCalls = 0;

    /** @param list<?EgressTransportEmission> $emissions */
    public function __construct(private array $emissions, private mixed $afterNext = null) {}

    public function next(): ?EgressTransportEmission
    {
        ++$this->nextCalls;
        $emission = array_shift($this->emissions);
        if (is_callable($this->afterNext)) {
            ($this->afterNext)();
            $this->afterNext = null;
        }

        return $emission;
    }

    public function close(): void
    {
        ++$this->closeCalls;
    }
}

final class AcceptanceTransport implements EgressTransport
{
    public int $capabilityCalls = 0;
    public int $openCalls = 0;
    public ?EgressTransportRequest $request = null;

    public function __construct(private readonly TransportCapabilitySet $capabilities, private readonly EgressTransportSession $session) {}

    public function capabilities(): TransportCapabilitySet
    {
        ++$this->capabilityCalls;

        return $this->capabilities;
    }

    public function open(EgressTransportRequest $request): EgressTransportSession
    {
        ++$this->openCalls;
        $this->request = $request;

        return $this->session;
    }
}

final class Phase15NetworkEgressAcceptance
{
    private const int STREAM_LIMIT = 1_048_576;
    private const int PROCESS_TIMEOUT_SECONDS = 20;
    private const string FAILURE = "phase15_acceptance_failed\n";
    private const string HOST = 'selected-address-proof.invalid';
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
                $workspace = new TemporaryNetworkEgressWorkspace($this->repositoryRoot);
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

    /** @param resource $stdout */
    private function successChild($stdout): int
    {
        $workspace = new TemporaryNetworkEgressWorkspace($this->repositoryRoot);
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

    private function accept(TemporaryNetworkEgressWorkspace $workspace, int $port): void
    {
        $source = $this->source($port);
        $same = $this->source($port);
        $this->case($source->equals($same));
        $this->case(OriginScheme::Http === $source->allowedOrigins()[0]->scheme());
        $this->case(self::HOST === $source->allowedOrigins()[0]->host());
        $this->case(AddressScope::ExplicitPrivateAllowed === $source->addressScope());
        $this->case(TransportSecurity::ExplicitHttpAllowed === $source->transportSecurity());
        $this->case(RedirectMode::Deny === $source->redirectMode());
        $this->case(ProxyMode::Deny === $source->proxyMode());
        $this->case(CookieMode::Deny === $source->cookieMode());
        $this->case(CredentialMode::None === $source->credentialMode());
        $this->case($source->requiredCapabilities()->contains(TransportCapability::Http11));
        $this->case(1 === $source->budget()->maximumConnectionAttempts());
        $this->case($this->fails(fn() => $this->source($port, 'INVALID')));

        $builder = new EgressPolicyArtifactBuilder();
        $artifact = $builder->build($source);
        $again = $builder->build($same);
        $validator = new EgressPolicyArtifactValidator();
        $this->case($artifact->canonicalBytes() === $again->canonicalBytes());
        $this->case(hash('sha256', $artifact->canonicalBytes()) === $artifact->artifactSha256());
        $this->case($validator->validateStructure($artifact->canonicalBytes())->artifactSha256() === $artifact->artifactSha256());
        $this->case($validator->validateCurrent($source, $artifact->canonicalBytes())->sourceSha256() === $artifact->sourceSha256());
        $this->case($this->fails(fn() => $validator->validateStructure(substr($artifact->canonicalBytes(), 0, -1))));
        $this->case($this->fails(fn() => $validator->validateCurrent($this->source($port, 'phase15.stale'), $artifact->canonicalBytes())));
        $policyDirectory = $workspace->directory('policies');
        $publisher = new EgressPolicyArtifactPublisher();
        $artifactPath = $publisher->publish($artifact, $policyDirectory);
        $this->case(is_file($artifactPath));
        $this->case($artifactPath === $publisher->publish($artifact, $policyDirectory));

        $profile = (new NetworkEgressApplicationProfileFactory())->create($source, $artifact->canonicalBytes());
        $applicationRoot = $workspace->directory('application');
        $generated = $applicationRoot . '/generated';
        $this->require(mkdir($generated, 0700));
        $build = (new NetworkEgressApplicationBuildWorkflow())->build($applicationRoot, 'generated', $source, $artifact->canonicalBytes());
        $wiring = $build->generatedWiring();
        $wiringValidator = new NetworkEgressWiringValidator();
        $this->case(is_file($build->publishedPath()));
        $this->case($wiringValidator->validateStructure($wiring->canonicalBytes())->generatedWiringSha256() === $wiring->generatedWiringSha256());
        $this->case($wiringValidator->validateCurrent($profile, $wiring->canonicalBytes())->generatedWiringSha256() === $wiring->generatedWiringSha256());
        $this->case($this->fails(fn() => $wiringValidator->validateStructure(substr($wiring->canonicalBytes(), 0, -1))));
        $this->case($build->publishedPath() === (new NetworkEgressWiringPublisher())->publish($applicationRoot, 'generated', $wiring));
        $this->case(!$build->loadedWiring()->started());

        $address = new ResolvedAddress('127.0.0.1');
        $resolution = new EgressResolutionResult(new ResolutionEvidence([$address], $address), null);
        $resolver = new AcceptanceResolver($resolution);
        $transport = new CurlEgressTransport(new CurlTransportProfile(new CurlTlsTrust(self::certificateShape()), true));
        $application = $build->loadedWiring()->start($resolver, $transport);
        $operation = $this->operation($source);
        $authority = new AcceptanceAuthorityCurrentness();
        $result = $application->run(new NetworkEgressApplicationRunInput(
            $operation,
            new EgressReceiptIdentity('receipt.phase15.acceptance'),
            new EgressAttemptIdentity('attempt.phase15.acceptance'),
            $authority,
            new AcceptanceCancellation(),
            new AcceptanceClock(),
        ));
        $operationResult = $result->operationResult();
        $response = $operationResult->response();
        $this->case(EgressOutcome::Succeeded === $operationResult->receipt()->outcome());
        $this->case(EgressLifecycleState::Succeeded === $operationResult->receipt()->finalLifecycleState());
        $this->case(1 === $operationResult->receipt()->connectionAttemptCount());
        $this->case(NegotiatedProtocol::Http11 === $operationResult->receipt()->negotiatedProtocol());
        $this->case(EgressTransferState::ResponseReceived === $operationResult->receipt()->transfer()->state());
        $this->case($operationResult->receipt()->transfer()->requestBytes() > 0);
        $this->case($operationResult->receipt()->transfer()->responseBytes() > 0);
        $this->case(null !== $response && 200 === $response->getStatusCode());
        $this->case(null !== $response && 'phase15-loopback-proof' === (string) $response->getBody());
        $this->case(1 === $resolver->calls && true === $resolver->origin?->equals($operation->origin()));
        $this->case($authority->calls >= 2);
        $this->case(NetworkEgressApplicationState::Retired === $application->state() && !$application->hasActiveOperation());
        $this->case($this->fails(fn() => $application->run(new NetworkEgressApplicationRunInput($operation, new EgressReceiptIdentity('receipt.reuse'), new EgressAttemptIdentity('attempt.reuse'), new AcceptanceAuthorityCurrentness(), new AcceptanceCancellation(), new AcceptanceClock()))));

        $outcomes = $this->outcomes($source, $artifact->canonicalBytes(), $operation, $result);
        $this->case(array_keys($outcomes) === array_map(static fn(EgressOutcome $outcome): string => $outcome->value, EgressOutcome::cases()));
        $inspector = new NetworkEgressInspector();
        foreach ($outcomes as $outcome => $runtimeResult) {
            $inspection = $inspector->inspect(new NetworkEgressInspectionRequest($profile, $wiring, $operation, $runtimeResult));
            $this->case($inspection->runtimeOutcome()?->value === $outcome);
            $this->case($inspection->cleanupComplete());
            $limitations = $inspection->toArray()['limitations'];
            if (!is_array($limitations)) {
                throw new RuntimeException('phase15_acceptance_failed');
            }
            $this->case('not-performed' === $limitations['replay']);
            $this->case('not-established' === $limitations['network_effect']);
        }
        $inspection = $inspector->inspect(new NetworkEgressInspectionRequest($profile, $wiring, $operation, $result));
        $inspectionAgain = $inspector->inspect(new NetworkEgressInspectionRequest($profile, $wiring, $operation, $result));
        $this->case($inspection->canonicalBytes() === $inspectionAgain->canonicalBytes());
        $this->case($inspection->runtimeEvidenceSupplied());
        foreach (NetworkEgressInspectionDeveloperRole::cases() as $role) {
            $view = (new NetworkEgressIntelligenceDeriver())->derive($inspection, $role);
            $verification = (new NetworkEgressIntelligenceVerifier())->verify($inspection, $view);
            $this->case(NetworkEgressIntelligenceVerdict::Pass === $verification->verdict());
            $this->case(NetworkEgressGraphEvidence::RELATIONSHIP === $view->graphEvidence()->relationship());
            $this->case('not-performed' === $view->roleExecution() && 'not-performed' === $view->networkAccess());
            $this->case('evidence-only' === $view->authority() && 'not-performed' === $view->repair());
        }
        $context = ObservabilityContext::uncorrelated();
        $observation = (new NetworkEgressObservationProjector())->project($inspection, $context);
        $audit = (new NetworkEgressSecurityAuditProjector())->project($inspection, $context);
        $this->case('kongono.network-egress.inspection' === $observation->identity()->name());
        $this->case('kongono.network-egress.inspection.security' === $audit->definition()->identity()->name());

        $overBudget = new EgressRuntimeResult($result->operationResult(), $operation->budget()->totalTimeoutMilliseconds() + 1, $result->resolutionCandidateCount(), $result->progressEmissionCount(), $result->peakBufferedBytes(), $result->finalRetainedEvidenceBytes());
        $this->case($this->fails(fn() => $inspector->inspect(new NetworkEgressInspectionRequest($profile, $wiring, $operation, $overBudget))));
        $unsupportedSource = $this->source($port, 'phase15.unsupported', new TransportCapabilitySet([TransportCapability::Http11, TransportCapability::RequestStreaming]));
        $unsupportedProfile = (new NetworkEgressApplicationProfileFactory())->create($unsupportedSource, $builder->build($unsupportedSource)->canonicalBytes());
        $fakeSession = new AcceptanceTransportSession([]);
        $this->case($this->fails(fn() => new \Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplication($unsupportedProfile, new AcceptanceResolver($resolution), new AcceptanceTransport(new TransportCapabilitySet([TransportCapability::Http11]), $fakeSession))));

        $public = self::successBytes($this->caseCount + 1);
        foreach ([self::HOST, '127.0.0.1', (string) $port, $workspace->root(), 'phase15.acceptance', 'receipt.phase15.acceptance'] as $secret) {
            $this->case(!str_contains($public, $secret));
        }
    }

    /** @return array<string, EgressRuntimeResult> */
    private function outcomes(EgressPolicySource $source, string $artifactBytes, EgressOperation $operation, EgressRuntimeResult $succeeded): array
    {
        $capabilities = new TransportCapabilitySet([TransportCapability::Http11]);
        $address = new ResolvedAddress('127.0.0.1');
        $resolution = new EgressResolutionResult(new ResolutionEvidence([$address], $address), null);
        $emptySession = new AcceptanceTransportSession([]);
        $rejected = $this->runtime($source, $artifactBytes, $operation, new AcceptanceAuthorityCurrentness([false]), new AcceptanceResolver($resolution), new AcceptanceTransport($capabilities, $emptySession), new AcceptanceCancellation());
        $cancelled = $this->runtime($source, $artifactBytes, $operation, new AcceptanceAuthorityCurrentness(), new AcceptanceResolver($resolution), new AcceptanceTransport($capabilities, new AcceptanceTransportSession([])), new AcceptanceCancellation(true));
        $failedResolution = new EgressResolutionResult(null, new EgressFailure(EgressFailureKind::Resolution, 'resolution_failed'));
        $failed = $this->runtime($source, $artifactBytes, $operation, new AcceptanceAuthorityCurrentness(), new AcceptanceResolver($failedResolution), new AcceptanceTransport($capabilities, new AcceptanceTransportSession([])), new AcceptanceCancellation());
        $terminal = new EgressTransportEmission(new EgressTransferEvidence(EgressTransferState::ResponseReceived, 64, 22), NegotiatedProtocol::Http11, 22, 64, new Response(), null);
        $uncertainSession = new AcceptanceTransportSession([$terminal, $terminal]);
        $uncertain = $this->runtime($source, $artifactBytes, $operation, new AcceptanceAuthorityCurrentness(), new AcceptanceResolver($resolution), new AcceptanceTransport($capabilities, $uncertainSession), new AcceptanceCancellation());
        $afterCancellation = new AcceptanceCancellation();
        $progress = new EgressTransportEmission(new EgressTransferEvidence(EgressTransferState::RequestSent, 64, 0), NegotiatedProtocol::Http11, 0, 32, null, null);
        $afterSession = new AcceptanceTransportSession([$progress], static function () use ($afterCancellation): void {
            $afterCancellation->requested = true;
        });
        $cancelledAfter = $this->runtime($source, $artifactBytes, $operation, new AcceptanceAuthorityCurrentness(), new AcceptanceResolver($resolution), new AcceptanceTransport($capabilities, $afterSession), $afterCancellation);

        $this->case(1 === $uncertainSession->closeCalls && 1 === $afterSession->closeCalls);

        return [
            EgressOutcome::RejectedBeforeAttempt->value => $rejected,
            EgressOutcome::Succeeded->value => $succeeded,
            EgressOutcome::FailedBeforeEffect->value => $failed,
            EgressOutcome::CancelledBeforeEffect->value => $cancelled,
            EgressOutcome::CancelledAfterPossibleEffect->value => $cancelledAfter,
            EgressOutcome::UncertainEffect->value => $uncertain,
        ];
    }

    private function runtime(EgressPolicySource $source, string $artifactBytes, EgressOperation $operation, EgressAuthorityCurrentness $authority, EgressResolver $resolver, EgressTransport $transport, EgressCancellation $cancellation): EgressRuntimeResult
    {
        return (new NetworkEgressRuntime())->run(new EgressRuntimeRequest($operation, $source, $artifactBytes, new EgressReceiptIdentity('receipt.phase15.outcome'), new EgressAttemptIdentity('attempt.phase15.outcome'), $authority, $resolver, $transport, $cancellation, new AcceptanceClock()));
    }

    private function source(int $port, string $identity = 'phase15.acceptance', ?TransportCapabilitySet $capabilities = null): EgressPolicySource
    {
        return new EgressPolicySource(
            new EgressPolicyIdentity($identity),
            [new CanonicalOrigin(OriginScheme::Http, self::HOST, $port)],
            AddressScope::ExplicitPrivateAllowed,
            TransportSecurity::ExplicitHttpAllowed,
            RedirectMode::Deny,
            ProxyMode::Deny,
            CookieMode::Deny,
            CredentialMode::None,
            new EgressBudget(1, 0, 1, 65_536, 65_536, 32, 8_192, 65_536, 8_192, 5_000, 5_000, 10_000),
            $capabilities ?? new TransportCapabilitySet([TransportCapability::Http11]),
        );
    }

    private function operation(EgressPolicySource $source): EgressOperation
    {
        $origin = $source->allowedOrigins()[0];
        $uri = 'http://' . self::HOST . ':' . $origin->port() . '/phase15?proof=selected_address';

        return new EgressOperation(
            new EgressOperationIdentity('operation.phase15.acceptance'),
            new ServerRequest('GET', $uri),
            $origin,
            new EgressAuthorityReference('authority.phase15.acceptance', 1),
            (new EgressPolicyArtifactBuilder())->build($source)->policyReference(),
            $source->budget(),
        );
    }

    /** @param resource $stdout */
    private function respond($stdout): int
    {
        $server = @stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
        unset($errorCode, $errorMessage);
        if (!is_resource($server)) {
            throw new RuntimeException('phase15_responder_failed');
        }
        $client = null;
        try {
            $name = stream_socket_get_name($server, false);
            if (false === $name || 1 !== preg_match('/\A127\.0\.0\.1:([1-9][0-9]{0,4})\z/D', $name, $match) || (int) $match[1] > 65_535) {
                throw new RuntimeException('phase15_responder_failed');
            }
            $port = (int) $match[1];
            fwrite($stdout, 'READY:' . $port . "\n");
            fflush($stdout);
            $client = @stream_socket_accept($server, 5);
            if (!is_resource($client)) {
                throw new RuntimeException('phase15_responder_failed');
            }
            stream_set_timeout($client, 5);
            $request = '';
            while (!str_contains($request, "\r\n\r\n")) {
                $chunk = fread($client, 4_096);
                if (false === $chunk || '' === $chunk) {
                    $metadata = stream_get_meta_data($client);
                    if ($metadata['timed_out'] || feof($client)) {
                        throw new RuntimeException('phase15_responder_failed');
                    }
                    continue;
                }
                $request .= $chunk;
                if (strlen($request) > 65_536) {
                    throw new RuntimeException('phase15_responder_failed');
                }
            }
            [$head, $tail] = explode("\r\n\r\n", $request, 2);
            if ('' !== $tail) {
                throw new RuntimeException('phase15_responder_failed');
            }
            $lines = explode("\r\n", $head);
            if ('GET /phase15?proof=selected_address HTTP/1.1' !== array_shift($lines)) {
                throw new RuntimeException('phase15_responder_failed');
            }
            $hosts = [];
            foreach ($lines as $line) {
                $position = strpos($line, ':');
                if (false === $position) {
                    throw new RuntimeException('phase15_responder_failed');
                }
                $name = strtolower(substr($line, 0, $position));
                $value = trim(substr($line, $position + 1));
                if ('host' === $name) {
                    $hosts[] = $value;
                }
                if (in_array($name, ['content-length', 'transfer-encoding'], true)) {
                    throw new RuntimeException('phase15_responder_failed');
                }
            }
            if ([self::HOST . ':' . $port] !== $hosts) {
                throw new RuntimeException('phase15_responder_failed');
            }
            $body = 'phase15-loopback-proof';
            $response = $this->loopbackResponse($body);
            if (strlen($response) > 4_096) {
                throw new RuntimeException('phase15_responder_failed');
            }
            $written = 0;
            while ($written < strlen($response)) {
                $count = fwrite($client, substr($response, $written));
                if (false === $count || 0 === $count) {
                    throw new RuntimeException('phase15_responder_failed');
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
                    throw new RuntimeException('phase15_responder_failed');
                }

                return (int) $match[1];
            }
            usleep(10_000);
        }
        throw new RuntimeException('phase15_responder_failed');
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
            throw new RuntimeException('phase15_process_failed');
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
                throw new RuntimeException('phase15_process_failed');
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
            throw new RuntimeException('phase15_process_failed');
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
        $paths = glob(rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/') . '/kongono-phase15-*', GLOB_NOSORT);
        if (false === $paths) {
            throw new RuntimeException('phase15_acceptance_failed');
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
            && 'kongono-phase15-network-egress' === $decoded['profile']
            && is_int($decoded['case_count'])
            && $decoded['case_count'] > 0
            && 'complete' === $decoded['cleanup']
            && self::successBytes($decoded['case_count']) === $bytes;
    }

    private function loopbackResponse(string $body): string
    {
        return sprintf("HTTP/1.1 200 OK\r\nContent-Length: %d\r\nContent-Type: text/plain\r\nConnection: close\r\n\r\n%s", strlen($body), $body);
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
            throw new RuntimeException('phase15_acceptance_failed');
        }
    }

    private static function successBytes(int $caseCount): string
    {
        return json_encode(['status' => 'ok', 'profile' => 'kongono-phase15-network-egress', 'case_count' => $caseCount, 'cleanup' => 'complete'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
    }

    private static function certificateShape(): string
    {
        return "-----BEGIN CERTIFICATE-----\nAA==\n-----END CERTIFICATE-----\n";
    }
}
