<?php

declare(strict_types=1);

namespace Kongono\Tests\External\AgentPrincipal;

use Closure;
use Kongono\AgentAdmission\Admission\AgentAdmissionBuilder;
use Kongono\AgentAdmission\Admission\AgentAdmissionDecision;
use Kongono\AgentAdmission\Admission\AgentAdmissionRequest;
use Kongono\AgentAdmission\Authority\AgentAuthorityPlan;
use Kongono\AgentAdmission\Authority\CapabilityAuthorityStatus;
use Kongono\AgentAdmission\Evidence\CapabilityAuthorizationEvidence;
use Kongono\AgentApplication\Generated\AgentWiringGenerator;
use Kongono\AgentApplication\Generated\AgentWiringPublisher;
use Kongono\AgentApplication\Generated\AgentWiringValidator;
use Kongono\AgentApplication\Profile\AgentApplicationProfileFactory;
use Kongono\AgentApplication\Runtime\AgentApplicationRunInput;
use Kongono\AgentApplication\Runtime\AgentWiringLoader;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactPublisher;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactValidator;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclaration;
use Kongono\AgentDeclarationBuild\Declaration\AgentDeclarationIdentity;
use Kongono\AgentInspection\Derivation\AgentIntelligenceDeriver;
use Kongono\AgentInspection\Derivation\AgentIntelligenceView;
use Kongono\AgentInspection\Graph\AgentGraphEvidence;
use Kongono\AgentInspection\Graph\AgentGraphEvidenceProjector;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspection;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspectionRequest;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspector;
use Kongono\AgentInspection\Projection\AgentInspectionObservationProjector;
use Kongono\AgentInspection\Projection\AgentInspectionSecurityAuditProjector;
use Kongono\AgentInspection\Role\DeveloperIntelligenceRole;
use Kongono\AgentInspection\Verification\AgentIntelligenceVerdict;
use Kongono\AgentInspection\Verification\AgentIntelligenceVerifier;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;
use Kongono\AgentPrincipalContract\Capability\CapabilityPurpose;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequest;
use Kongono\AgentPrincipalContract\Capability\CapabilityRequirement;
use Kongono\AgentPrincipalContract\Constraint\AgentConstraint;
use Kongono\AgentPrincipalContract\Constraint\ConstraintKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceIdentity;
use Kongono\AgentPrincipalContract\Evidence\EvidenceKind;
use Kongono\AgentPrincipalContract\Evidence\EvidenceReference;
use Kongono\AgentPrincipalContract\Identity\AgentPrincipalReference;
use Kongono\AgentPrincipalContract\Identity\OperationReference;
use Kongono\AgentPrincipalContract\Intent\AgentIntent;
use Kongono\AgentPrincipalContract\Intent\IntentIdentity;
use Kongono\AgentPrincipalContract\Intent\IntentPurpose;
use Kongono\AgentPrincipalContract\Proposal\ActionProposal;
use Kongono\AgentPrincipalContract\Proposal\ProposalIdentity;
use Kongono\AgentPrincipalContract\Provenance\AgentProvenance;
use Kongono\AgentPrincipalContract\Provenance\ProvenanceSource;
use Kongono\AgentPrincipalContract\Visibility\AgentVisibility;
use Kongono\AgentRuntime\Authority\AgentAuthorityCurrentness;
use Kongono\AgentRuntime\Budget\AgentRuntimeBudget;
use Kongono\AgentRuntime\Cancellation\AgentCancellation;
use Kongono\AgentRuntime\Capability\AgentCapabilityInvocation;
use Kongono\AgentRuntime\Capability\AgentCapabilityOutcome;
use Kongono\AgentRuntime\Capability\AgentCapabilityPort;
use Kongono\AgentRuntime\Capability\AgentCapabilityResult;
use Kongono\AgentRuntime\Clock\AgentMonotonicClock;
use Kongono\AgentRuntime\Proposal\AgentProposalPort;
use Kongono\AgentRuntime\Proposal\AgentProposalRequest;
use Kongono\AgentRuntime\Proposal\AgentProposalResult;
use Kongono\AgentRuntime\Runtime\AgentRuntime;
use Kongono\AgentRuntime\Runtime\AgentRuntimeOutcome;
use Kongono\AgentRuntime\Runtime\AgentRuntimeRequest;
use Kongono\AgentRuntime\Runtime\AgentRuntimeState;
use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole as GraphDeveloperIntelligenceRole;
use Kongono\Authentication\AuthenticationFacts;
use Kongono\Authentication\AuthenticationInstant;
use Kongono\Authentication\AuthenticationMechanism;
use Kongono\Authentication\AuthenticationStrength;
use Kongono\Authentication\AuthenticationVerification;
use Kongono\Authorization\AuthorizationDecisionReceipt;
use Kongono\Authorization\AuthorizationEnvironmentEvidence;
use Kongono\Authorization\AuthorizationEnvironmentProfile;
use Kongono\Authorization\AuthorizationEvaluation;
use Kongono\Authorization\AuthorizationPolicyEvidence;
use Kongono\Authorization\AuthorizationPolicyName;
use Kongono\Authorization\AuthorizationPolicyProvenance;
use Kongono\Authorization\AuthorizationPolicyReference;
use Kongono\Authorization\AuthorizationPolicyVersion;
use Kongono\Authorization\AuthorizationRequest;
use Kongono\IdentityAccess\Action;
use Kongono\IdentityAccess\AuthorizationDecision;
use Kongono\IdentityAccess\PrincipalId;
use Kongono\IdentityAccess\PrincipalKind;
use Kongono\IdentityAccess\PrincipalReference;
use Kongono\IdentityAccess\ResourceId;
use Kongono\IdentityAccess\ResourceReference;
use Kongono\IdentityAccess\ResourceType;
use Kongono\IdentityAccess\SecurityContext;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationKind;
use Kongono\SecurityAudit\SecurityAuditOutcome;
use RuntimeException;
use Throwable;

final class AcceptanceAuthorityCurrentness implements AgentAuthorityCurrentness
{
    public int $calls = 0;

    /** @param non-empty-list<bool|Throwable> $observations */
    public function __construct(private array $observations = [true, true], private ?Closure $record = null) {}

    public function isCurrent(AgentAuthorityPlan $plan, OperationReference $operation): bool
    {
        unset($plan, $operation);
        ($this->record ?? static function (): void {})('authority');
        $value = $this->observations[min($this->calls, count($this->observations) - 1)];
        ++$this->calls;
        if ($value instanceof Throwable) {
            throw $value;
        }

        return $value;
    }
}

final class AcceptanceCancellation implements AgentCancellation
{
    public int $calls = 0;

    /** @param non-empty-list<bool|Throwable> $observations */
    public function __construct(private array $observations = [false, false, false, false]) {}

    public function isCancellationRequested(): bool
    {
        $value = $this->observations[min($this->calls, count($this->observations) - 1)];
        ++$this->calls;
        if ($value instanceof Throwable) {
            throw $value;
        }

        return $value;
    }
}

final class AcceptanceClock implements AgentMonotonicClock
{
    public int $calls = 0;

    /** @param non-empty-list<int|Throwable> $values */
    public function __construct(private array $values = [0, 0, 0, 0, 0]) {}

    public function milliseconds(): int
    {
        $value = $this->values[min($this->calls, count($this->values) - 1)];
        ++$this->calls;
        if ($value instanceof Throwable) {
            throw $value;
        }

        return $value;
    }
}

final class AcceptanceProposalPort implements AgentProposalPort
{
    public int $proposeCalls = 0;
    public int $closeCalls = 0;

    public function __construct(
        private AgentProposalResult|Throwable $result,
        private ?Throwable $closeFailure = null,
        private ?Closure $record = null,
    ) {}

    public function propose(AgentProposalRequest $request): AgentProposalResult
    {
        unset($request);
        ++$this->proposeCalls;
        ($this->record ?? static function (): void {})('proposal');
        if ($this->result instanceof Throwable) {
            throw $this->result;
        }

        return $this->result;
    }

    public function close(): void
    {
        ++$this->closeCalls;
        ($this->record ?? static function (): void {})('proposal:close');
        if (null !== $this->closeFailure) {
            throw $this->closeFailure;
        }
    }
}

final class AcceptanceCapabilityPort implements AgentCapabilityPort
{
    public int $invokeCalls = 0;
    public int $closeCalls = 0;

    public function __construct(
        private CapabilityIdentity $identity,
        private AgentCapabilityResult|Throwable $result,
        private ?Throwable $closeFailure = null,
        private ?Closure $record = null,
    ) {}

    public function capabilityIdentity(): CapabilityIdentity
    {
        return $this->identity;
    }

    public function invoke(AgentCapabilityInvocation $invocation): AgentCapabilityResult
    {
        unset($invocation);
        ++$this->invokeCalls;
        ($this->record ?? static function (): void {})($this->identity->value() . ':invoke');
        if ($this->result instanceof Throwable) {
            throw $this->result;
        }

        return $this->result;
    }

    public function close(): void
    {
        ++$this->closeCalls;
        ($this->record ?? static function (): void {})($this->identity->value() . ':close');
        if (null !== $this->closeFailure) {
            throw $this->closeFailure;
        }
    }
}

final class Phase13AgentPrincipalAcceptance
{
    private const int STREAM_LIMIT = 1_048_576;
    private const int REQUIRED_CASES = 40;
    private const string FAILURE_TOKEN = "phase13_acceptance_failed\n";

    private int $caseCount = 0;
    /** @var array<int, true> */
    private array $cases = [];
    private int $activeProcesses = 0;
    /** @var list<string> */
    private array $observedStreams = [];
    /** @var list<string> */
    private array $observedFailures = [];

    public function __construct(private readonly string $repositoryRoot) {}

    /** @param list<string> $arguments
     *  @param resource $stdout
     *  @param resource $stderr
     */
    public function run(array $arguments, $stdout, $stderr): int
    {
        try {
            $this->require([] === $arguments);
            $before = $this->repositoryFingerprint();
            $mode = getenv('KONGONO_PHASE13_ACCEPTANCE_CHILD');
            if ('failure' === $mode) {
                throw new RuntimeException('intentional_failure');
            }
            if ('success' === $mode) {
                $workspace = new TemporaryAgentPrincipalWorkspace();
                try {
                    $workspace->create($this->repositoryRoot);
                    $workspace->identity();
                } finally {
                    $workspace->cleanup();
                }
                $this->require($workspace->cleanupComplete() && $before === $this->repositoryFingerprint());
                fwrite($stdout, self::successBytes());

                return 0;
            }

            $workspace = new TemporaryAgentPrincipalWorkspace();
            try {
                $workspace->create($this->repositoryRoot);
                $this->accept($workspace);
                $workspace->identity();
            } finally {
                $workspace->cleanup();
            }
            $this->case(37, $workspace->cleanupComplete());
            $this->case(38, 0 === $this->activeProcesses && $before === $this->repositoryFingerprint());
            $this->case(39, !$this->containsAny(self::successBytes(), ['provider', 'account', 'network', 'model']));
            $this->case(40, !is_dir($this->repositoryRoot . '/packages/agent-provider'));
            $this->require(self::REQUIRED_CASES === $this->caseCount);
            $this->assertNoDisclosure();
            fwrite($stdout, self::successBytes());

            return 0;
        } catch (Throwable) {
            fwrite($stderr, self::FAILURE_TOKEN);

            return 1;
        }
    }

    private function accept(TemporaryAgentPrincipalWorkspace $workspace): void
    {
        $declaration = $this->declaration();
        $this->case(1, 2 === count($declaration->capabilityRequests()) && 9 === count($declaration->constraints()) && AgentVisibility::Protected === $declaration->visibility());
        $this->expectFailure(static fn(): AgentDeclarationIdentity => new AgentDeclarationIdentity('INVALID ID'));
        $this->expectFailure(fn(): AgentDeclaration => $this->declaration([$this->capability('calendar.read'), $this->capability('calendar.read')]));
        $this->case(2, true);
        $this->case(3, !$this->isInstanceOf($declaration, AgentAuthorityCurrentness::class) && !$this->isInstanceOf($declaration->capabilityRequests()[0], AgentCapabilityPort::class) && !$this->isCallable($declaration));

        $artifactBuilder = new AgentDeclarationArtifactBuilder();
        $artifact = $artifactBuilder->build($declaration);
        $repeatedArtifact = $artifactBuilder->build($declaration);
        $this->case(4, $artifact->canonicalBytes() === $repeatedArtifact->canonicalBytes() && $artifact->artifactSha256() === $repeatedArtifact->artifactSha256());
        $artifactValidator = new AgentDeclarationArtifactValidator();
        $structuredArtifact = $artifactValidator->validateStructure($artifact->canonicalBytes());
        $artifactValidator->validateCurrent($declaration, $artifact->canonicalBytes());
        $this->case(5, $artifact->artifactSha256() === $structuredArtifact->artifactSha256());
        foreach (['', '{', substr($artifact->canonicalBytes(), 0, -1), str_repeat('x', 131_073), str_replace('"schema"', '"zchema"', $artifact->canonicalBytes())] as $invalid) {
            $this->expectFailure(static fn() => $artifactValidator->validateStructure($invalid));
        }
        $this->expectFailure(fn() => $artifactValidator->validateCurrent($this->declaration(identity: 'assistant.changed'), $artifact->canonicalBytes()));
        $this->case(6, true);
        $artifactPublisher = new AgentDeclarationArtifactPublisher();
        $artifactPath = $artifactPublisher->publish($artifact, $workspace->path('declaration'));
        $this->require($artifactPath === $artifactPublisher->publish($artifact, $workspace->path('declaration')));
        $conflictArtifact = $workspace->write('conflict/kongono-agent-declaration-v1-' . $artifact->artifactSha256() . '.json', '{}');
        $this->expectFailure(static fn() => $artifactPublisher->publish($artifact, dirname($conflictArtifact)));
        $this->case(7, is_file($artifactPath));

        $request = $this->admissionRequest($declaration, $artifact->canonicalBytes());
        $this->case(8, null !== $request->authenticationVerification()->facts() && 2 === count($request->authorizationEvidence()));
        $admissionBuilder = new AgentAdmissionBuilder();
        $plan = $admissionBuilder->build($request);
        $repeatedPlan = $admissionBuilder->build($request);
        $this->case(9, AgentAdmissionDecision::Admitted === $plan->admissionDecision() && $plan->identity()->equals($repeatedPlan->identity()));
        $this->proveAdmissionDenials($admissionBuilder, $declaration, $artifact->canonicalBytes());
        $this->case(10, true);
        $this->case(11, 2 === count($plan->capabilityDecisions()) && array_all($plan->capabilityDecisions(), static fn($decision): bool => $decision->permitted()));

        $events = [];
        $record = static function (string $event) use (&$events): void {
            $events[] = $event;
        };
        [$runtimeResult, $proposalPort, $capabilityPorts, $currentness] = $this->runRuntime($declaration, $plan, $record);
        $this->case(12, 2 === $currentness->calls && 1 === $proposalPort->proposeCalls && 2 === count($capabilityPorts));
        $this->case(13, AgentRuntimeOutcome::Performed === $runtimeResult->outcome() && 1 === $proposalPort->proposeCalls && 1 === $capabilityPorts[0]->invokeCalls && 0 === $capabilityPorts[1]->invokeCalls);
        $this->proveBudgets($declaration, $plan);
        $this->case(14, true);
        $this->case(15, ['authority', 'proposal', 'authority', 'calendar.read:invoke', 'mail.read:close', 'calendar.read:close', 'proposal:close'] === $events);
        $this->proveRuntimeFailures($declaration, $plan);
        $this->case(16, true);
        $healthy = $this->runRuntime($declaration, $plan);
        $this->case(17, AgentRuntimeState::Retired === $runtimeResult->terminalState() && $runtimeResult->cleanupCompletedWithoutFailure() && AgentRuntimeOutcome::Performed === $healthy[0]->outcome());

        $profile = (new AgentApplicationProfileFactory())->create($declaration, $artifact->canonicalBytes(), $plan);
        $this->case(18, $profile->declarationArtifact()->artifactSha256() === $artifact->artifactSha256() && $profile->authorityPlan()->identity()->equals($plan->identity()));
        $wiringGenerator = new AgentWiringGenerator();
        $wiring = $wiringGenerator->generate($profile);
        $repeatedWiring = $wiringGenerator->generate($profile);
        $this->case(19, $wiring->canonicalBytes() === $repeatedWiring->canonicalBytes() && $wiring->generatedWiringSha256() === $repeatedWiring->generatedWiringSha256());
        $wiringValidator = new AgentWiringValidator();
        $wiringValidator->validateStructure($wiring->canonicalBytes());
        $wiringValidator->validateCurrent($profile, $wiring->canonicalBytes());
        $wiringPublisher = new AgentWiringPublisher();
        $wiringPath = $wiringPublisher->publish($workspace->path(), $workspace->path('wiring'), $wiring);
        $this->require($wiringPath === $wiringPublisher->publish($workspace->path(), $workspace->path('wiring'), $wiring));
        $conflictWiring = $workspace->write('conflict/kongono-agent-wiring-v1-' . $wiring->generatedWiringSha256() . '.php', '<?php');
        $this->expectFailure(static fn() => $wiringPublisher->publish($workspace->path(), dirname($conflictWiring), $wiring));
        $this->case(20, is_file($wiringPath));

        $inspectionWithoutRuntime = (new AgentPrincipalInspector())->inspect(new AgentPrincipalInspectionRequest($profile, $wiring));
        $this->case(25, !class_exists($wiring->generatedClass(), false) && !$inspectionWithoutRuntime->runtimeEvidenceSupplied());
        $loaded = (new AgentWiringLoader())->load($workspace->path(), $profile, $wiringPath);
        $this->case(21, class_exists($wiring->generatedClass(), false) && !$loaded->started());
        $loaderSource = file_get_contents($this->repositoryRoot . '/packages/agent-application/src/Runtime/AgentWiringLoader.php');
        $this->require(false !== $loaderSource);
        $this->case(22, !$this->containsAny($loaderSource . $wiring->canonicalBytes(), ['glob(', 'scandir(', 'getenv(', 'Container', 'ServiceLocator', 'Registry', 'providerLookup']));
        $application = $loaded->start();
        [$applicationInput, $applicationProposal, $applicationCapabilities] = $this->applicationInput($declaration);
        $applicationResult = $application->run($applicationInput);
        $this->case(23, AgentRuntimeOutcome::Performed === $applicationResult->outcome() && 1 === $applicationProposal->closeCalls && array_all($applicationCapabilities, static fn(AcceptanceCapabilityPort $port): bool => 1 === $port->closeCalls));

        $inspection = (new AgentPrincipalInspector())->inspect(new AgentPrincipalInspectionRequest($profile, $wiring, $applicationResult));
        $this->case(24, 'not-sandboxed' === $inspection->trustedCodeIsolation());
        $this->case(26, $inspection->runtimeEvidenceSupplied() && AgentRuntimeOutcome::Performed === $inspection->runtimeOutcome() && $inspection->receiptCount() === count($applicationResult->receipts()));
        $this->proveDisclosure($inspection, $wiring->canonicalBytes(), $applicationResult);
        $this->case(27, true);

        $graph = $this->graphView(GraphDeveloperIntelligenceRole::Hantei);
        $graphEvidence = (new AgentGraphEvidenceProjector())->project($inspection, DeveloperIntelligenceRole::Hantei, $graph);
        $this->expectFailure(fn() => (new AgentGraphEvidenceProjector())->project($inspection, DeveloperIntelligenceRole::Chie, $graph));
        $this->case(28, AgentGraphEvidence::SUPPLIED === $graphEvidence->toArray()['status']);
        $this->case(29, AgentGraphEvidence::RELATIONSHIP === $graphEvidence->relationship() && 'not-established' === $inspection->graphRelationship() && 'evidence-only' === $inspection->authority());
        $observation = (new AgentInspectionObservationProjector())->project($inspection, ObservabilityContext::uncorrelated());
        $audit = (new AgentInspectionSecurityAuditProjector())->project($inspection, ObservabilityContext::uncorrelated());
        $this->case(30, ObservationKind::OperationalEvent === $observation->kind() && 'performed' === $observation->attributes()->find(new AttributeKey('inspection.runtime_outcome'))?->value() && SecurityAuditOutcome::Succeeded === $audit->outcome());
        $views = $this->roleViews($inspection);
        $this->case(31, $this->roleViewsEquivalent($views));
        $this->proveEveryVerificationGroup($inspection, $graph);
        $this->case(32, true);

        $successOne = $this->child('success');
        $successTwo = $this->child('success');
        $this->case(33, 0 === $successOne->exitCode && $successOne->stdout === $successTwo->stdout && '' === $successOne->stderr);
        [$parallelOne, $parallelTwo] = $this->childPair();
        $this->case(34, 0 === $parallelOne->exitCode && 0 === $parallelTwo->exitCode && $parallelOne->stdout === $parallelTwo->stdout && '' === $parallelOne->stderr && '' === $parallelTwo->stderr);
        $failure = $this->child('failure');
        $this->case(35, 0 !== $failure->exitCode && '' === $failure->stdout && self::FAILURE_TOKEN === $failure->stderr);
        $this->case(36, $this->streamsAreSafe());
    }

    /** @param list<CapabilityRequest>|null $capabilities */
    private function declaration(?array $capabilities = null, string $identity = 'assistant.primary', string $principalId = 'private-principal-sentinel'): AgentDeclaration
    {
        return new AgentDeclaration(
            new AgentDeclarationIdentity($identity),
            new AgentPrincipalReference($this->principal($principalId)),
            $capabilities ?? [$this->capability('calendar.read'), $this->capability('mail.read', CapabilityRequirement::Optional)],
            $this->constraints(),
            [
                new EvidenceReference(EvidenceKind::Authentication, new EvidenceIdentity('auth.private'), str_repeat('1', 64)),
                new EvidenceReference(EvidenceKind::Policy, new EvidenceIdentity('policy.private'), str_repeat('2', 64)),
            ],
            new AgentProvenance(ProvenanceSource::Application, 'application.private', str_repeat('3', 64)),
            AgentVisibility::Protected,
        );
    }

    /** @return list<AgentConstraint> */
    private function constraints(): array
    {
        return [
            new AgentConstraint(ConstraintKind::CapabilityCallLimit, 1),
            new AgentConstraint(ConstraintKind::ConcurrencyLimit, 1),
            new AgentConstraint(ConstraintKind::DelegationDepthLimit, 0),
            new AgentConstraint(ConstraintKind::DurationMillisecondsLimit, 100),
            new AgentConstraint(ConstraintKind::OutputBytesLimit, 100),
            new AgentConstraint(ConstraintKind::RetainedStateBytesLimit, 0),
            new AgentConstraint(ConstraintKind::RetryLimit, 0),
            new AgentConstraint(ConstraintKind::StepLimit, 2),
            new AgentConstraint(ConstraintKind::TokenLimit, 100),
        ];
    }

    private function capability(string $identity, CapabilityRequirement $requirement = CapabilityRequirement::Required): CapabilityRequest
    {
        return new CapabilityRequest(new CapabilityIdentity($identity), new CapabilityPurpose('Private bounded purpose for ' . $identity), $requirement);
    }

    private function principal(string $id = 'private-principal-sentinel'): PrincipalReference
    {
        return new PrincipalReference(PrincipalKind::Agent, new PrincipalId($id));
    }

    private function authenticated(string $id = 'private-principal-sentinel'): AuthenticationVerification
    {
        return AuthenticationVerification::authenticated(new AuthenticationFacts(
            $this->principal($id),
            new AuthenticationMechanism('fixture.v1'),
            new AuthenticationStrength(2),
            new AuthenticationInstant(100),
            new AuthenticationInstant(200),
        ));
    }

    private function authorizationEvidence(string $capability, AuthorizationDecision $decision = AuthorizationDecision::Permit): CapabilityAuthorizationEvidence
    {
        $policy = new AuthorizationPolicyReference(
            new AuthorizationPolicyName('agent.acceptance'),
            new AuthorizationPolicyVersion('v1'),
            new AuthorizationPolicyProvenance('fixture.private'),
        );
        $policyEvidence = new class ($policy) implements AuthorizationPolicyEvidence {
            public function __construct(private AuthorizationPolicyReference $reference) {}
            public function reference(): AuthorizationPolicyReference
            {
                return $this->reference;
            }
        };
        $environment = new class implements AuthorizationEnvironmentEvidence {
            public function profile(): AuthorizationEnvironmentProfile
            {
                return new AuthorizationEnvironmentProfile('fixture.v1');
            }
        };
        $request = new AuthorizationRequest(
            SecurityContext::authenticated($this->principal()),
            new Action('agent.capability.use'),
            new ResourceReference(new ResourceType('agent.capability'), new ResourceId('private-resource-sentinel')),
            $policy,
            $policyEvidence,
            $environment,
        );

        return new CapabilityAuthorizationEvidence(new CapabilityIdentity($capability), new AuthorizationEvaluation(AuthorizationDecisionReceipt::evaluated($request, $decision)));
    }

    private function admissionRequest(AgentDeclaration $declaration, string $artifactBytes): AgentAdmissionRequest
    {
        return new AgentAdmissionRequest(
            $declaration,
            $artifactBytes,
            $this->authenticated(),
            [new CapabilityIdentity('calendar.read'), new CapabilityIdentity('mail.read')],
            [$this->authorizationEvidence('calendar.read'), $this->authorizationEvidence('mail.read')],
        );
    }

    private function proveAdmissionDenials(AgentAdmissionBuilder $builder, AgentDeclaration $declaration, string $artifactBytes): void
    {
        $missing = $builder->build(new AgentAdmissionRequest($declaration, $artifactBytes, $this->authenticated(), [new CapabilityIdentity('calendar.read')], []));
        $this->require(AgentAdmissionDecision::Denied === $missing->admissionDecision());
        $denied = $builder->build(new AgentAdmissionRequest($declaration, $artifactBytes, $this->authenticated(), [new CapabilityIdentity('calendar.read')], [$this->authorizationEvidence('calendar.read', AuthorizationDecision::Deny)]));
        $this->require(AgentAdmissionDecision::Denied === $denied->admissionDecision());
        $mismatch = $builder->build(new AgentAdmissionRequest($declaration, $artifactBytes, $this->authenticated('principal-other'), [], []));
        $this->require(AgentAdmissionDecision::Denied === $mismatch->admissionDecision());
        $undeclared = $builder->build(new AgentAdmissionRequest($declaration, $artifactBytes, $this->authenticated(), [new CapabilityIdentity('network.open')], [$this->authorizationEvidence('network.open')]));
        $undeclaredDecisions = $undeclared->capabilityDecisions();
        $undeclaredDecision = end($undeclaredDecisions);
        $this->require(false !== $undeclaredDecision && CapabilityAuthorityStatus::Undeclared === $undeclaredDecision->status());
        $other = $this->declaration(identity: 'assistant.changed');
        $this->expectFailure(fn() => $builder->build(new AgentAdmissionRequest($other, $artifactBytes, $this->authenticated(), [], [])));
    }

    private function operation(): OperationReference
    {
        return new OperationReference('operation.primary');
    }

    private function intent(): AgentIntent
    {
        return new AgentIntent(new IntentIdentity('intent.primary'), new IntentPurpose('Perform one private bounded operation'));
    }

    private function budget(): AgentRuntimeBudget
    {
        return new AgentRuntimeBudget(2, 100, 100, 1, 0, 0, 100, 0, 1);
    }

    private function proposal(AgentDeclaration $declaration): ActionProposal
    {
        return new ActionProposal(
            new ProposalIdentity('proposal.primary'),
            $declaration->principal(),
            $this->operation(),
            $this->intent(),
            $declaration->capabilityRequests()[0],
            $declaration->constraints(),
            $declaration->evidenceReferences(),
            $declaration->provenance(),
            $declaration->visibility(),
        );
    }

    private function proposalResult(AgentDeclaration $declaration): AgentProposalResult
    {
        return new AgentProposalResult($this->proposal($declaration), 5, 3, str_repeat('a', 64));
    }

    private function capabilityResult(AgentCapabilityOutcome $outcome = AgentCapabilityOutcome::Performed): AgentCapabilityResult
    {
        return new AgentCapabilityResult($outcome, 'private-result-sentinel', str_repeat('b', 64));
    }

    /** @return array{\Kongono\AgentRuntime\Runtime\AgentRuntimeResult, AcceptanceProposalPort, list<AcceptanceCapabilityPort>, AcceptanceAuthorityCurrentness} */
    private function runRuntime(AgentDeclaration $declaration, AgentAuthorityPlan $plan, ?Closure $record = null): array
    {
        $proposal = new AcceptanceProposalPort($this->proposalResult($declaration), record: $record);
        $ports = [
            new AcceptanceCapabilityPort(new CapabilityIdentity('calendar.read'), $this->capabilityResult(), record: $record),
            new AcceptanceCapabilityPort(new CapabilityIdentity('mail.read'), $this->capabilityResult(), record: $record),
        ];
        $currentness = new AcceptanceAuthorityCurrentness(record: $record);
        $result = (new AgentRuntime())->run(new AgentRuntimeRequest(
            $declaration,
            $plan,
            $this->operation(),
            $this->intent(),
            $this->budget(),
            $proposal,
            $ports,
            $currentness,
            new AcceptanceCancellation(),
            new AcceptanceClock(),
        ));

        return [$result, $proposal, $ports, $currentness];
    }

    private function proveBudgets(AgentDeclaration $declaration, AgentAuthorityPlan $plan): void
    {
        $this->require([2, 100, 100, 1, 0, 0, 100, 0, 1] === $this->budget()->values());
        $limits = [3, 101, 101, 2, 1, 1, 101, 1, 2];
        foreach (array_keys($limits) as $position) {
            $values = $this->budget()->values();
            $values[$position] = $limits[$position];
            $proposal = new AcceptanceProposalPort($this->proposalResult($declaration));
            $ports = [
                new AcceptanceCapabilityPort(new CapabilityIdentity('calendar.read'), $this->capabilityResult()),
                new AcceptanceCapabilityPort(new CapabilityIdentity('mail.read'), $this->capabilityResult()),
            ];
            $this->expectFailure(fn() => (new AgentRuntime())->run(new AgentRuntimeRequest(
                $declaration,
                $plan,
                $this->operation(),
                $this->intent(),
                new AgentRuntimeBudget(...$values),
                $proposal,
                $ports,
                new AcceptanceAuthorityCurrentness(),
                new AcceptanceCancellation(),
                new AcceptanceClock(),
            )));
        }
    }

    private function proveRuntimeFailures(AgentDeclaration $declaration, AgentAuthorityPlan $plan): void
    {
        $factory = function (
            AcceptanceProposalPort $proposal,
            array $ports,
            AcceptanceAuthorityCurrentness $currentness = new AcceptanceAuthorityCurrentness(),
            AcceptanceCancellation $cancellation = new AcceptanceCancellation(),
        ) use ($declaration, $plan): AgentRuntimeRequest {
            return new AgentRuntimeRequest($declaration, $plan, $this->operation(), $this->intent(), $this->budget(), $proposal, $ports, $currentness, $cancellation, new AcceptanceClock());
        };
        $ports = fn(): array => [
            new AcceptanceCapabilityPort(new CapabilityIdentity('calendar.read'), $this->capabilityResult()),
            new AcceptanceCapabilityPort(new CapabilityIdentity('mail.read'), $this->capabilityResult()),
        ];
        $proposal = fn(): AcceptanceProposalPort => new AcceptanceProposalPort($this->proposalResult($declaration));

        $denied = (new AgentRuntime())->run($factory($proposal(), $ports(), new AcceptanceAuthorityCurrentness([false])));
        $cancelled = (new AgentRuntime())->run($factory($proposal(), $ports(), cancellation: new AcceptanceCancellation([true])));
        $stale = (new AgentRuntime())->run($factory($proposal(), $ports(), new AcceptanceAuthorityCurrentness([true, false])));
        $this->require(AgentRuntimeOutcome::AuthorityDenied === $denied->outcome());
        $this->require(AgentRuntimeOutcome::Cancelled === $cancelled->outcome());
        $this->require(AgentRuntimeOutcome::AuthorityDenied === $stale->outcome());

        $this->expectFailure(fn() => (new AgentRuntime())->run($factory(new AcceptanceProposalPort(new RuntimeException('private-proposal-failure')), $ports())));
        $failedPorts = [
            new AcceptanceCapabilityPort(new CapabilityIdentity('calendar.read'), new RuntimeException('private-capability-failure')),
            new AcceptanceCapabilityPort(new CapabilityIdentity('mail.read'), $this->capabilityResult()),
        ];
        $this->expectFailure(fn() => (new AgentRuntime())->run($factory($proposal(), $failedPorts)));
        $cleanupPorts = [
            new AcceptanceCapabilityPort(new CapabilityIdentity('calendar.read'), $this->capabilityResult(), new RuntimeException('private-cleanup-failure')),
            new AcceptanceCapabilityPort(new CapabilityIdentity('mail.read'), $this->capabilityResult()),
        ];
        $this->expectFailure(fn() => (new AgentRuntime())->run($factory($proposal(), $cleanupPorts)));
    }

    /** @return array{AgentApplicationRunInput, AcceptanceProposalPort, list<AcceptanceCapabilityPort>} */
    private function applicationInput(AgentDeclaration $declaration): array
    {
        $proposal = new AcceptanceProposalPort($this->proposalResult($declaration));
        $ports = [
            new AcceptanceCapabilityPort(new CapabilityIdentity('calendar.read'), $this->capabilityResult()),
            new AcceptanceCapabilityPort(new CapabilityIdentity('mail.read'), $this->capabilityResult()),
        ];
        $input = new AgentApplicationRunInput(
            $this->operation(),
            $this->intent(),
            $this->budget(),
            $proposal,
            $ports,
            new AcceptanceAuthorityCurrentness(),
            new AcceptanceCancellation(),
            new AcceptanceClock(),
        );

        return [$input, $proposal, $ports];
    }

    private function graphView(GraphDeveloperIntelligenceRole $role): ApplicationGraphIntelligenceView
    {
        return new ApplicationGraphIntelligenceView(
            $role,
            str_repeat('d', 64),
            new GraphIdentity(str_repeat('e', 64)),
            GraphContractVersion::Version1,
            4,
            4,
            ['composition.input' => 1, 'composition.service' => 1, 'composition.contract' => 1, 'composition.root' => 1],
            ['composition.depends_on' => 1, 'composition.consumes_input' => 1, 'composition.implements_contract' => 1, 'composition.exposes_root' => 1],
        );
    }

    /** @return list<AgentIntelligenceView> */
    private function roleViews(AgentPrincipalInspection $inspection): array
    {
        $deriver = new AgentIntelligenceDeriver();

        return array_map(static fn(DeveloperIntelligenceRole $role): AgentIntelligenceView => $deriver->derive($inspection, $role), DeveloperIntelligenceRole::cases());
    }

    /** @param list<AgentIntelligenceView> $views */
    private function roleViewsEquivalent(array $views): bool
    {
        $normalized = [];
        foreach ($views as $view) {
            $data = $view->toArray();
            unset($data['role'], $data['purpose'], $data['serialization_sha256']);
            if (is_array($data['graph_evidence'] ?? null)) {
                $data['graph_evidence']['role'] = 'normalized';
            }
            $normalized[] = json_encode($data, JSON_THROW_ON_ERROR);
            $this->require('not-performed' === $view->roleExecution() && 'not-performed' === $view->repair());
        }

        return 1 === count(array_unique($normalized));
    }

    private function proveEveryVerificationGroup(AgentPrincipalInspection $inspection, ApplicationGraphIntelligenceView $graph): void
    {
        $deriver = new AgentIntelligenceDeriver();
        $verifier = new AgentIntelligenceVerifier();
        $exact = $deriver->derive($inspection, DeveloperIntelligenceRole::Hantei, $graph);
        $this->require(AgentIntelligenceVerdict::Pass === $verifier->verify($inspection, $exact, $graph)->verdict());
        $candidates = [
            new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'explain', $inspection, $exact->graphEvidence()),
            new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $this->tamperInspection($inspection, $inspection->identity(), str_repeat('0', 64)), $exact->graphEvidence()),
            new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $this->tamperInspection($inspection, 'assistant.primary', 'assistant.changed'), $exact->graphEvidence()),
            new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $this->tamperInspection($inspection, 'receiptCount";i:' . $inspection->receiptCount() . ';', 'receiptCount";i:0;'), $exact->graphEvidence()),
            new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $inspection, new AgentGraphEvidence($inspection->identity(), DeveloperIntelligenceRole::Hantei, false)),
            new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $this->tamperInspection($inspection, 'safe-aggregate-only', 'not-established----'), $exact->graphEvidence()),
            new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $inspection, $exact->graphEvidence(), roleExecution: 'not-observed'),
            new AgentIntelligenceView(DeveloperIntelligenceRole::Hantei, 'verify', $inspection, $exact->graphEvidence(), serializationSha256: str_repeat('0', 64)),
        ];
        $tokens = [
            'role-purpose-failed',
            'inspection-identity-schema-failed',
            'inspection-aggregate-evidence-failed',
            'runtime-summary-evidence-failed',
            'graph-evidence-failed',
            'disclosure-currentness-authority-semantics-failed',
            'role-execution-repair-hidden-access-semantics-failed',
            'deterministic-serialization-failed',
        ];
        foreach ($candidates as $index => $candidate) {
            $verification = $verifier->verify($inspection, $candidate, 4 === $index ? $graph : $graph);
            $this->require(AgentIntelligenceVerdict::Fail === $verification->verdict() && in_array($tokens[$index], $verification->failureTokens(), true));
        }
    }

    private function tamperInspection(AgentPrincipalInspection $inspection, string $from, string $to): AgentPrincipalInspection
    {
        $this->require(strlen($from) === strlen($to));
        $serialized = str_replace($from, $to, serialize($inspection), $replacements);
        $this->require(1 === $replacements);
        $candidate = unserialize($serialized, ['allowed_classes' => true]);
        if (!$candidate instanceof AgentPrincipalInspection) {
            throw new RuntimeException('tampered_inspection_invalid');
        }

        return $candidate;
    }

    private function proveDisclosure(AgentPrincipalInspection $inspection, string $generatedBytes, \Kongono\AgentRuntime\Runtime\AgentRuntimeResult $result): void
    {
        unset($result);
        $bounded = json_encode($inspection->toArray(), JSON_THROW_ON_ERROR);
        foreach (['private-principal-sentinel', 'Private bounded purpose', 'auth.private', 'policy.private', 'application.private', 'private-result-sentinel', '<?php', $generatedBytes] as $private) {
            $this->require(!str_contains($bounded, $private));
        }
        $this->require('safe-aggregate-only' === $inspection->disclosure());
    }

    private function child(string $mode): ProcessResult
    {
        return $this->process([PHP_BINARY, __DIR__ . '/run.php'], ['KONGONO_PHASE13_ACCEPTANCE_CHILD' => $mode]);
    }

    /** @return array{ProcessResult, ProcessResult} */
    private function childPair(): array
    {
        $first = $this->open([PHP_BINARY, __DIR__ . '/run.php'], ['KONGONO_PHASE13_ACCEPTANCE_CHILD' => 'success']);
        $second = $this->open([PHP_BINARY, __DIR__ . '/run.php'], ['KONGONO_PHASE13_ACCEPTANCE_CHILD' => 'success']);

        return [$this->close($first), $this->close($second)];
    }

    /** @param list<string> $command
     *  @param array<string, string> $environment
     */
    private function process(array $command, array $environment = []): ProcessResult
    {
        return $this->close($this->open($command, $environment));
    }

    /** @param list<string> $command
     *  @param array<string, string> $environment
     *  @return array{resource, array<int, resource>}
     */
    private function open(array $command, array $environment): array
    {
        $pipes = [];
        $inherited = getenv();
        $env = array_merge(array_filter($inherited, 'is_string'), $environment);
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->repositoryRoot, $env);
        if (!is_resource($process)) {
            throw new RuntimeException('process_start_failed');
        }
        fclose($pipes[0]);
        ++$this->activeProcesses;

        return [$process, $pipes];
    }

    /** @param array{resource, array<int, resource>} $opened */
    private function close(array $opened): ProcessResult
    {
        [$process, $pipes] = $opened;
        $stdout = stream_get_contents($pipes[1], self::STREAM_LIMIT + 1);
        $stderr = stream_get_contents($pipes[2], self::STREAM_LIMIT + 1);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        --$this->activeProcesses;
        if (false === $stdout || false === $stderr || strlen($stdout) > self::STREAM_LIMIT || strlen($stderr) > self::STREAM_LIMIT) {
            throw new RuntimeException('stream_limit');
        }
        $this->observedStreams[] = $stdout;
        $this->observedStreams[] = $stderr;

        return new ProcessResult($exit, $stdout, $stderr);
    }

    private function repositoryFingerprint(): string
    {
        $parts = [];
        foreach ([
            ['git', '-c', 'safe.directory=' . $this->repositoryRoot, 'status', '--porcelain=v1', '-z', '--untracked-files=all'],
            ['git', '-c', 'safe.directory=' . $this->repositoryRoot, 'show-ref'],
            ['git', '-c', 'safe.directory=' . $this->repositoryRoot, 'rev-parse', 'HEAD'],
            ['git', '-c', 'safe.directory=' . $this->repositoryRoot, 'diff', '--cached', '--raw', '-z'],
        ] as $command) {
            $result = $this->process($command);
            $this->require(0 === $result->exitCode);
            $parts[] = $result->stdout;
        }
        foreach (['.git/index', '.git/config', 'composer.lock'] as $path) {
            $digest = hash_file('sha256', $this->repositoryRoot . '/' . $path);
            if (false === $digest) {
                throw new RuntimeException('fingerprint_failed');
            }
            $parts[] = strtoupper($digest);
        }

        return strtoupper(hash('sha256', implode("\0", $parts)));
    }

    private function streamsAreSafe(): bool
    {
        $joined = implode('', $this->observedStreams) . implode('', $this->observedFailures);
        foreach ([$this->repositoryRoot, str_replace('/', '\\', $this->repositoryRoot), 'private-principal-sentinel', 'Private bounded purpose', 'auth.private', 'policy.private', 'application.private', 'private-result-sentinel', 'private-resource-sentinel', 'private-proposal-failure', 'private-capability-failure', 'private-cleanup-failure', '<?php'] as $private) {
            if ('' !== $private && str_contains($joined, $private)) {
                return false;
            }
        }

        return true;
    }

    private function assertNoDisclosure(): void
    {
        $this->require($this->streamsAreSafe());
    }

    /** @param callable(): mixed $operation */
    private function expectFailure(callable $operation): Throwable
    {
        try {
            $operation();
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            $text = $failure->getMessage() . '|' . (string) $failure;
            $this->require(!str_contains($text, $this->repositoryRoot));
            $this->observedFailures[] = $text;

            return $failure;
        }
        throw new RuntimeException('failure_expected');
    }

    private function case(int $number, bool $passed): void
    {
        $this->require($number >= 1 && $number <= self::REQUIRED_CASES && !isset($this->cases[$number]));
        $this->require($passed);
        $this->cases[$number] = true;
        ++$this->caseCount;
    }

    private function require(bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException('acceptance_condition_failed');
        }
    }

    /** @param class-string $type */
    private function isInstanceOf(object $value, string $type): bool
    {
        return is_a($value, $type);
    }

    private function isCallable(mixed $value): bool
    {
        return is_callable($value);
    }

    /** @param list<string> $needles */
    private function containsAny(string $bytes, array $needles): bool
    {
        return array_any($needles, static fn(string $needle): bool => str_contains($bytes, $needle));
    }

    private static function successBytes(): string
    {
        return json_encode(['status' => 'ok', 'profile' => 'kongono-phase13-agent-principal', 'case_count' => self::REQUIRED_CASES, 'cleanup' => 'complete'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
