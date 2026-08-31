<?php

declare(strict_types=1);

namespace Kongono\Tests\External\ExtensionPlatform;

use Kongono\ApplicationGraphBuild\Ir\GraphFieldRecord;
use Kongono\ApplicationGraphBuild\Ir\GraphNodeRecord;
use Kongono\ApplicationGraphContract\ContractVersion as GraphContractVersion;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Schema\FieldName;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ExtensionAdmission\Admission\ExtensionAdmissionBuilder;
use Kongono\ExtensionAdmission\Contribution\ExtensionGraphContribution;
use Kongono\ExtensionAdmission\Projection\ExtensionGraphProvenance;
use Kongono\ExtensionAdmission\Selection\ExtensionAdmissionPlan;
use Kongono\ExtensionAdmission\Selection\ExtensionSelection;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactBuilder;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactPublisher;
use Kongono\ExtensionBuild\Artifact\ExtensionDeclarationArtifactValidator;
use Kongono\ExtensionComposition\Generated\ExtensionWiringGenerator;
use Kongono\ExtensionComposition\Generated\ExtensionWiringPublisher;
use Kongono\ExtensionComposition\Generated\ExtensionWiringValidator;
use Kongono\ExtensionComposition\Grant\CapabilityDecision;
use Kongono\ExtensionComposition\Grant\CapabilityProviderLifetime;
use Kongono\ExtensionComposition\Implementation\TrustedExtensionImplementation;
use Kongono\ExtensionComposition\Plan\ExtensionGrantPlan;
use Kongono\ExtensionContract\Capability\CapabilityIdentity;
use Kongono\ExtensionContract\Capability\CapabilityPurpose;
use Kongono\ExtensionContract\Capability\CapabilityRequest;
use Kongono\ExtensionContract\Capability\CapabilityRequirement;
use Kongono\ExtensionContract\ContractVersion;
use Kongono\ExtensionContract\Contribution\ContributionDeclaration;
use Kongono\ExtensionContract\Contribution\ContributionIdentity;
use Kongono\ExtensionContract\Contribution\ContributionSchema;
use Kongono\ExtensionContract\Contribution\ContributionVisibility;
use Kongono\ExtensionContract\Declaration\ExtensionDeclaration;
use Kongono\ExtensionContract\ExtensionCategory;
use Kongono\ExtensionContract\Identity\ExtensionIdentity;
use Kongono\ExtensionContract\Lifecycle\ExtensionLifecycle;
use Kongono\ExtensionContract\Provenance\ExtensionProvenance;
use Kongono\ExtensionContract\Provenance\ExtensionSourceIdentity;
use Kongono\ExtensionContract\Version\ExtensionVersion;
use Kongono\ExtensionHost\Contract\ApplicationExtensionInterface;
use Kongono\ExtensionHost\Contract\OperationExtensionInterface;
use Kongono\ExtensionHost\Runtime\ExtensionHostLoader;
use Kongono\ExtensionHost\Runtime\ExtensionHostState;
use Kongono\ExtensionHost\Runtime\TrustedExtensionHost;
use Kongono\ExtensionInspection\Derivation\ExtensionIntelligenceDeriver;
use Kongono\ExtensionInspection\Derivation\ExtensionIntelligenceView;
use Kongono\ExtensionInspection\Inspection\ExtensionPlatformInspection;
use Kongono\ExtensionInspection\Loading\ExtensionInspectionLoader;
use Kongono\ExtensionInspection\Role\DeveloperIntelligenceRole;
use Kongono\ExtensionInspection\Verification\ExtensionIntelligenceVerdict;
use Kongono\ExtensionInspection\Verification\ExtensionIntelligenceVerifier;
use RuntimeException;
use Throwable;

interface AcceptanceApplicationPort
{
    public function record(string $event): void;
}

interface AcceptanceOperationPort
{
    public function record(string $event): void;
}

interface AcceptanceOptionalPort {}

final class AcceptanceOptionalProvider implements AcceptanceOptionalPort {}

final class AcceptanceRecorder implements AcceptanceApplicationPort, AcceptanceOperationPort
{
    /** @var list<string> */
    public array $events = [];

    public function record(string $event): void
    {
        $this->events[] = $event;
    }
}

final class AcceptanceApplicationExtension implements ApplicationExtensionInterface
{
    private AcceptanceApplicationPort $application;

    public function __construct(AcceptanceApplicationPort $application)
    {
        $this->application = $application;
    }

    public function start(): void
    {
        $this->application->record('application-start');
    }

    public function stop(): void
    {
        $this->application->record('application-stop');
    }
}

final class AcceptanceOperationExtension implements OperationExtensionInterface
{
    private AcceptanceApplicationPort $application;
    private AcceptanceOperationPort $operation;

    public function __construct(
        AcceptanceApplicationPort $application,
        AcceptanceOperationPort $operation,
        AcceptanceOptionalPort $optional = new AcceptanceOptionalProvider(),
    ) {
        $this->application = $application;
        $this->operation = $operation;
        unset($optional);
    }

    public function begin(): void
    {
        $this->application->record('operation-application-provider');
        $this->operation->record('operation-begin');
    }

    public function close(): void
    {
        $this->operation->record('operation-close');
    }
}

final class Phase12ExtensionPlatformAcceptance
{
    private const int STREAM_LIMIT = 1048576;
    private const int REQUIRED_CASES = 50;
    private int $caseCount = 0;
    /** @var array<int, true> */
    private array $cases = [];
    private int $activeProcesses = 0;
    /** @var list<string> */
    private array $observedStreams = [];

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
            $mode = getenv('KONGONO_PHASE12_ACCEPTANCE_CHILD');
            if ('failure' === $mode) {
                throw new RuntimeException('intentional_failure');
            }
            if ('success' === $mode) {
                $workspace = new TemporaryExtensionPlatformWorkspace();
                $workspace->create($this->repositoryRoot);
                $workspace->identity();
                $workspace->cleanup();
                $this->require($before === $this->repositoryFingerprint());
                fwrite($stdout, self::successBytes());

                return 0;
            }

            $workspace = new TemporaryExtensionPlatformWorkspace();
            try {
                $workspace->create($this->repositoryRoot);
                $this->accept($workspace);
                $workspace->identity();
            } finally {
                $workspace->cleanup();
            }
            $this->case(50, 0 === $this->activeProcesses && $before === $this->repositoryFingerprint());
            $this->require(self::REQUIRED_CASES === $this->caseCount);
            $this->assertNoDisclosure();
            fwrite($stdout, self::successBytes());

            return 0;
        } catch (Throwable) {
            fwrite($stderr, "phase12_acceptance_failed\n");

            return 1;
        }
    }

    private function accept(TemporaryExtensionPlatformWorkspace $workspace): void
    {
        [$application, $operation] = $this->declarations();
        $declarations = [$application, $operation];
        $this->case(1, 'example/application' === $application->identity()->value() && 1 === count($application->contributions()));
        $this->expectFailure(static fn(): CapabilityIdentity => new CapabilityIdentity('INVALID ID', 0));
        $this->expectFailure(static fn(): ExtensionDeclaration => new ExtensionDeclaration(ContractVersion::Version1, new ExtensionIdentity('example/duplicate'), new ExtensionVersion('v1'), ExtensionCategory::TrustedInProcess, new ExtensionProvenance(new ExtensionSourceIdentity(str_repeat('a', 64)), str_repeat('b', 64)), ExtensionLifecycle::Application, [$application->capabilityRequests()[0], $application->capabilityRequests()[0]], []));
        $this->case(2, true);

        $builder = new ExtensionDeclarationArtifactBuilder();
        $artifact = $builder->build($declarations);
        $repeated = $builder->build($declarations);
        $this->expectFailure(static fn() => $builder->build([$operation, $application]));
        $this->case(3, $artifact->canonicalBytes() === $repeated->canonicalBytes());
        $validator = new ExtensionDeclarationArtifactValidator();
        $structured = $validator->validateStructure($artifact->canonicalBytes());
        $this->case(4, $artifact->artifactSha256() === $structured->artifactSha256());
        $validator->validateCurrent($declarations, $artifact->canonicalBytes());
        $this->expectFailure(static fn() => $validator->validateCurrent([$application], $artifact->canonicalBytes()));
        $this->case(5, true);

        $declarationPublisher = new ExtensionDeclarationArtifactPublisher();
        $declarationPath = $declarationPublisher->publish($artifact, $workspace->path('declaration'));
        $this->require($declarationPath === $declarationPublisher->publish($artifact, $workspace->path('declaration')));
        $conflictPath = $workspace->path('conflict/kongono-extension-declarations-v1-' . $artifact->artifactSha256() . '.json');
        $workspace->write('conflict/' . basename($conflictPath), '{}');
        $this->expectFailure(static fn() => $declarationPublisher->publish($artifact, dirname($conflictPath)));
        $this->case(6, is_file($declarationPath));
        foreach (['', '{', substr($artifact->canonicalBytes(), 0, -1), str_repeat('x', ExtensionDeclarationArtifactBuilder::MAX_ARTIFACT_BYTES + 1)] as $invalidBytes) {
            $this->expectFailure(static fn() => $validator->validateStructure($invalidBytes));
        }
        $this->expectFailure(static fn() => (new ExtensionInspectionLoader())->load($workspace->path('missing.json'), $workspace->path('missing.php')));
        $this->case(7, true);

        $selections = [new ExtensionSelection($application->identity(), $application->version()), new ExtensionSelection($operation->identity(), $operation->version())];
        $plan = new ExtensionAdmissionPlan($artifact->declarationSetIdentity(), $selections, []);
        $bundles = [$this->bundle($application, '1'), $this->bundle($operation, '2')];
        $admissionBuilder = new ExtensionAdmissionBuilder();
        $admission = $admissionBuilder->build($declarations, $plan, $bundles);
        $this->case(8, $admission->admissionIdentity()->value() === $admissionBuilder->build($declarations, $plan, $bundles)->admissionIdentity()->value());
        $this->case(9, 2 === count($admission->admittedExtensions()));
        $this->expectFailure(static fn() => $admissionBuilder->build($declarations, new ExtensionAdmissionPlan($artifact->declarationSetIdentity(), [new ExtensionSelection($application->identity(), new ExtensionVersion('v2'))], []), $bundles));
        $this->case(10, true);
        $this->proveConflicts($admissionBuilder);
        $this->case(11, true);
        $this->case(12, 2 === count($admission->nodeRecords()) && ExtensionGraphProvenance::matches($admission->nodeRecords()[0]->provenance(), $application->identity(), $application->version(), $application->contributions()[0]));
        $this->case(13, NodeType::CompositionContract === $admission->nodeRecords()[0]->type());
        $repeatedAdmission = $admissionBuilder->build($declarations, new ExtensionAdmissionPlan($repeated->declarationSetIdentity(), $selections, []), $bundles);
        $this->case(14, $admission->admissionIdentity()->value() === $repeatedAdmission->admissionIdentity()->value());
        $this->case(15, !str_contains($this->safeFailures(), 'private-contribution-sentinel'));

        $requests = array_merge($application->capabilityRequests(), $operation->capabilityRequests());
        $this->case(16, 4 === count($requests));
        $grantPlan = $this->grantPlan($application, $operation, $admission->admissionIdentity());
        $this->case(17, 4 === count($grantPlan->decisions()));
        $this->case(18, 2 === count(array_filter($grantPlan->decisions(), static fn(CapabilityDecision $decision): bool => CapabilityProviderLifetime::Application === $decision->providerLifetime())));
        $this->expectFailure(static fn() => (new ExtensionWiringGenerator())->generate($declarations, $admission, new ExtensionGrantPlan($admission->admissionIdentity(), $grantPlan->implementations(), [])));
        $this->expectFailure(static fn() => new ExtensionGrantPlan($admission->admissionIdentity(), $grantPlan->implementations(), [$grantPlan->decisions()[0], $grantPlan->decisions()[0]]));
        $this->case(19, true);

        $generator = new ExtensionWiringGenerator();
        $wiring = $generator->generate($declarations, $admission, $grantPlan);
        $this->case(20, $wiring->canonicalBytes() === $generator->generate($declarations, $repeatedAdmission, $grantPlan)->canonicalBytes());
        $this->case(21, $wiring->declarationSetIdentity()->equals($artifact->declarationSetIdentity()) && $wiring->admissionIdentity()->value() === $admission->admissionIdentity()->value() && $wiring->grantPlanIdentity()->value() === $grantPlan->identity()->value());
        $wiringValidator = new ExtensionWiringValidator();
        $wiringValidator->validateStructure($wiring->canonicalBytes());
        $wiringValidator->validateCurrent($declarations, $admission, $grantPlan, $wiring->canonicalBytes());
        $this->expectFailure(static fn() => $wiringValidator->validateStructure($wiring->canonicalBytes() . 'x'));
        $this->case(22, !class_exists($wiring->applicationClass(), false));
        $wiringPublisher = new ExtensionWiringPublisher();
        $wiringPath = $wiringPublisher->publish($wiring, $workspace->path('wiring'));
        $this->require($wiringPath === $wiringPublisher->publish($wiring, $workspace->path('wiring')));
        $workspace->write('conflict/' . $wiring->wiringSha256() . '.php', '<?php');
        $this->expectFailure(static fn() => $wiringPublisher->publish($wiring, $workspace->path('conflict')));
        $this->case(23, is_file($wiringPath));
        $this->case(24, !$this->containsForbiddenConstruct($wiring->canonicalBytes()));

        $inspection = (new ExtensionInspectionLoader())->load($declarationPath, $wiringPath);
        $this->case(34, 2 === $inspection->declarationCount());
        $mismatchArtifact = $builder->build([$application]);
        $mismatchPath = $workspace->write('children/mismatch.json', $mismatchArtifact->canonicalBytes());
        $this->expectFailure(static fn() => (new ExtensionInspectionLoader())->load($mismatchPath, $wiringPath));
        $this->case(35, true);
        $this->case(36, !class_exists($wiring->applicationClass(), false) && !class_exists($wiring->operationClass(), false));
        $this->case(37, 2 === $inspection->implementationCount() && 4 === $inspection->decisionCount() && 4 === $inspection->capabilityRequestCount() && 2 === $inspection->contributionCount());
        $this->case(38, 'safe-aggregate-only' === $inspection->visibility());
        $views = $this->roleViews($inspection);
        $this->case(39, $this->roleViewsEquivalent($views));
        $verification = (new ExtensionIntelligenceVerifier())->verify($inspection, $views[0]);
        $this->require(ExtensionIntelligenceVerdict::Pass === $verification->verdict());
        $this->case(40, $this->proveVerificationMismatch($inspection, $views[0]));

        $beforeArtifacts = $workspace->identity();
        $machine = $this->kongo(['--json', 'extension:inspect', '--declaration-artifact-path=' . $declarationPath, '--generated-wiring-path=' . $wiringPath, '--no-interaction']);
        $this->case(41, 0 === $machine->exitCode && '' === $machine->stderr && str_ends_with($machine->stdout, "\n"));
        $human = $this->kongo(['extension:inspect', '--declaration-artifact-path=' . $declarationPath, '--generated-wiring-path=' . $wiringPath, '--no-interaction']);
        $this->case(42, 0 === $human->exitCode && '' === $human->stderr && str_ends_with($human->stdout, "\n"));
        $missing = $this->kongo(['--json', 'extension:inspect', '--declaration-artifact-path=' . $workspace->path('missing.json'), '--generated-wiring-path=' . $wiringPath, '--no-interaction']);
        $this->case(43, 3 === $missing->exitCode && '' === $missing->stderr && str_contains($missing->stdout, 'KGN-CONSOLE-APPLICATION-1005'));
        $this->case(44, $beforeArtifacts === $workspace->identity());
        $this->case(45, 'evidence-only' === $inspection->authority() && 'not-observed' === $inspection->execution());

        $loaded = (new ExtensionHostLoader())->load($wiring, $grantPlan, $wiringPath);
        $this->case(25, class_exists($wiring->applicationClass(), false) && class_exists($wiring->operationClass(), false));
        $loaderSource = (string) file_get_contents($this->repositoryRoot . '/packages/extension-host/src/Runtime/ExtensionHostLoader.php');
        $this->case(26, !$this->containsAny($loaderSource, ['glob(', 'scandir(', 'getenv(', '->invoke(', 'newInstance(', 'Composer', 'Container', 'ServiceLocator', 'Registry']));
        $recorder = new AcceptanceRecorder();
        $host = TrustedExtensionHost::start($loaded, $recorder, $recorder);
        $this->case(27, ['application-start'] === $recorder->events);
        $firstOperation = $host->beginOperation($recorder);
        $firstOperation->close();
        $firstOperation->close();
        $this->case(28, 1 === count(array_filter($recorder->events, static fn(string $event): bool => 'operation-close' === $event)));
        $this->case(29, 1 === count(array_filter($recorder->events, static fn(string $event): bool => 'operation-application-provider' === $event)));
        $secondOperation = $host->beginOperation($recorder);
        $host->retire();
        $this->expectFailure(static fn() => $host->beginOperation($recorder));
        $secondOperation->close();
        $this->case(30, ExtensionHostState::Retired === $host->state());
        $host->shutdown();
        $this->case(31, $host->isClosed() && 'application-stop' === end($recorder->events));
        $this->case(32, 2 === count(array_filter($recorder->events, static fn(string $event): bool => 'operation-begin' === $event)));
        $this->case(33, 'not-sandboxed' === $inspection->trustedCodeIsolation());

        $successOne = $this->child('success');
        $successTwo = $this->child('success');
        $this->case(46, 0 === $successOne->exitCode && $successOne->stdout === $successTwo->stdout && '' === $successOne->stderr);
        [$parallelOne, $parallelTwo] = $this->childPair();
        $this->case(47, 0 === $parallelOne->exitCode && 0 === $parallelTwo->exitCode && $parallelOne->stdout === $parallelTwo->stdout);
        $failure = $this->child('failure');
        $this->case(48, 0 !== $failure->exitCode && '' === $failure->stdout && "phase12_acceptance_failed\n" === $failure->stderr);
        $this->case(49, $this->streamsAreSafe());
    }

    /** @return array{ExtensionDeclaration, ExtensionDeclaration} */
    private function declarations(): array
    {
        $applicationRequest = $this->request('application', CapabilityRequirement::Required);
        $operationRequests = [$this->request('application', CapabilityRequirement::Required), $this->request('operation', CapabilityRequirement::Required), $this->request('optional', CapabilityRequirement::Optional)];
        $application = $this->declaration('example/application', ExtensionLifecycle::Application, [$applicationRequest], ContributionVisibility::Application, 'application-graph');
        $operation = $this->declaration('example/operation', ExtensionLifecycle::Operation, $operationRequests, ContributionVisibility::FrameworkInternal, 'operation-graph');

        return [$application, $operation];
    }

    /** @param list<CapabilityRequest> $requests */
    private function declaration(string $identity, ExtensionLifecycle $lifecycle, array $requests, ContributionVisibility $visibility, string $contribution): ExtensionDeclaration
    {
        return new ExtensionDeclaration(
            ContractVersion::Version1,
            new ExtensionIdentity($identity),
            new ExtensionVersion('v1'),
            ExtensionCategory::TrustedInProcess,
            new ExtensionProvenance(new ExtensionSourceIdentity(str_repeat('a', 64)), str_repeat('b', 64)),
            $lifecycle,
            $requests,
            [new ContributionDeclaration(new ContributionIdentity($contribution), new ContributionSchema('kongono.application-graph-ir', 1), $visibility, str_repeat('c', 64))],
        );
    }

    private function request(string $identity, CapabilityRequirement $requirement): CapabilityRequest
    {
        return new CapabilityRequest(new CapabilityIdentity($identity, 1), new CapabilityPurpose('use_' . $identity), $requirement);
    }

    private function bundle(ExtensionDeclaration $declaration, string $seed): ExtensionGraphContribution
    {
        $contribution = $declaration->contributions()[0];
        $node = new GraphNodeRecord(
            GraphContractVersion::Version1,
            new NodeIdentity(str_repeat($seed, 64)),
            NodeType::CompositionContract,
            [new GraphFieldRecord(new FieldName('symbol'), 'Acceptance\\Contract' . $seed)],
            ExtensionGraphProvenance::create($declaration->identity(), $declaration->version(), $contribution),
        );

        return new ExtensionGraphContribution($declaration->identity(), $declaration->version(), $contribution->identity(), [$node], []);
    }

    private function proveConflicts(ExtensionAdmissionBuilder $builder): void
    {
        $alpha = $this->declaration('example/conflict-alpha', ExtensionLifecycle::Application, [], ContributionVisibility::Public, 'shared-graph');
        $beta = $this->declaration('example/conflict-beta', ExtensionLifecycle::Application, [], ContributionVisibility::Public, 'shared-graph');
        $artifact = (new ExtensionDeclarationArtifactBuilder())->build([$alpha, $beta]);
        $selections = [new ExtensionSelection($alpha->identity(), $alpha->version()), new ExtensionSelection($beta->identity(), $beta->version())];
        $this->expectFailure(fn() => $builder->build([$alpha, $beta], new ExtensionAdmissionPlan($artifact->declarationSetIdentity(), $selections, []), [$this->bundle($alpha, '3'), $this->bundle($beta, '4')]));
    }

    private function grantPlan(ExtensionDeclaration $application, ExtensionDeclaration $operation, \Kongono\ExtensionAdmission\Identity\AdmissionIdentity $admissionIdentity): ExtensionGrantPlan
    {
        $applicationRequest = $application->capabilityRequests()[0];
        $operationRequests = $operation->capabilityRequests();

        return new ExtensionGrantPlan($admissionIdentity, [
            new TrustedExtensionImplementation($application->identity(), $application->version(), AcceptanceApplicationExtension::class),
            new TrustedExtensionImplementation($operation->identity(), $operation->version(), AcceptanceOperationExtension::class),
        ], [
            $this->grant($application, $applicationRequest, 'application', AcceptanceApplicationPort::class, CapabilityProviderLifetime::Application),
            $this->grant($operation, $operationRequests[0], 'application', AcceptanceApplicationPort::class, CapabilityProviderLifetime::Application),
            $this->grant($operation, $operationRequests[1], 'operation', AcceptanceOperationPort::class, CapabilityProviderLifetime::Operation),
            CapabilityDecision::deny($operation->identity(), $operation->version(), $operationRequests[2]->identity(), $operationRequests[2]->purpose(), $operationRequests[2]->requirement(), 'optional', AcceptanceOptionalPort::class),
        ]);
    }

    private function grant(ExtensionDeclaration $declaration, CapabilityRequest $request, string $parameter, string $provider, CapabilityProviderLifetime $lifetime): CapabilityDecision
    {
        return CapabilityDecision::grant($declaration->identity(), $declaration->version(), $request->identity(), $request->purpose(), $request->requirement(), $parameter, $provider, $lifetime);
    }

    /** @return list<ExtensionIntelligenceView> */
    private function roleViews(ExtensionPlatformInspection $inspection): array
    {
        $deriver = new ExtensionIntelligenceDeriver();

        return array_map(static fn(DeveloperIntelligenceRole $role): ExtensionIntelligenceView => $deriver->derive($inspection, $role), DeveloperIntelligenceRole::cases());
    }

    /** @param list<ExtensionIntelligenceView> $views */
    private function roleViewsEquivalent(array $views): bool
    {
        $normalized = [];
        foreach ($views as $view) {
            $data = $view->toArray();
            unset($data['role']);
            $normalized[] = json_encode($data, JSON_THROW_ON_ERROR);
        }

        return 1 === count(array_unique($normalized));
    }

    private function proveVerificationMismatch(ExtensionPlatformInspection $inspection, ExtensionIntelligenceView $view): bool
    {
        $mismatches = [
            ['declarationArtifactSha256', str_repeat('f', 64), 'identity-binding-failed'],
            ['implementationCount', $view->implementationCount() + 1, 'declaration-counts-failed'],
            ['categoryCounts', ['core' => 1, 'trusted_in_process' => 2, 'sandboxed' => 0, 'external' => 0], 'category-lifecycle-counts-failed'],
            ['requiredCapabilityRequestCount', $view->requiredCapabilityRequestCount() + 1, 'capability-counts-failed'],
            ['contributionCount', $view->contributionCount() + 1, 'contribution-counts-failed'],
            ['visibility', 'unsafe', 'visibility-semantics-failed'],
            ['authority', 'granted', 'authority-runtime-semantics-failed'],
        ];
        foreach ($mismatches as [$property, $value, $token]) {
            $tampered = $this->tamperedView($view, $property, $value);
            $verification = (new ExtensionIntelligenceVerifier())->verify($inspection, $tampered);
            if (ExtensionIntelligenceVerdict::Fail !== $verification->verdict() || [$token] !== $verification->failureTokens()) {
                return false;
            }
        }

        return true;
    }

    private function tamperedView(ExtensionIntelligenceView $view, string $name, mixed $replacement): ExtensionIntelligenceView
    {
        $reflection = new \ReflectionClass($view);
        /** @var ExtensionIntelligenceView $copy */
        $copy = $reflection->newInstanceWithoutConstructor();
        foreach ($reflection->getProperties() as $property) {
            $property->setValue($copy, $property->getName() === $name ? $replacement : $property->getValue($view));
        }

        return $copy;
    }

    private function containsForbiddenConstruct(string $bytes): bool
    {
        $tokens = ['ReflectionClass', 'getenv(', 'putenv(', 'proc_open(', 'curl_', 'new PDO', 'include ', 'require ', 'ev' . 'al(', 'Container', 'ServiceLocator', 'Registry'];

        return $this->containsAny($bytes, $tokens);
    }

    /** @param list<string> $tokens */
    private function containsAny(string $bytes, array $tokens): bool
    {
        return array_any($tokens, static fn(string $token): bool => str_contains($bytes, $token));
    }

    /** @param list<string> $arguments */
    private function kongo(array $arguments): ProcessResult
    {
        return $this->process(array_merge([PHP_BINARY, $this->repositoryRoot . '/vendor/bin/kongo'], $arguments));
    }

    private function child(string $mode): ProcessResult
    {
        return $this->process([PHP_BINARY, __DIR__ . '/run.php'], ['KONGONO_PHASE12_ACCEPTANCE_CHILD' => $mode]);
    }

    /** @return array{ProcessResult, ProcessResult} */
    private function childPair(): array
    {
        $first = $this->open([PHP_BINARY, __DIR__ . '/run.php'], ['KONGONO_PHASE12_ACCEPTANCE_CHILD' => 'success']);
        $second = $this->open([PHP_BINARY, __DIR__ . '/run.php'], ['KONGONO_PHASE12_ACCEPTANCE_CHILD' => 'success']);

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

        return new ProcessResult(true, $exit, $stdout, $stderr);
    }

    private function repositoryFingerprint(): string
    {
        $parts = [];
        foreach ([
            ['git', '-c', 'safe.directory=' . $this->repositoryRoot, 'status', '--porcelain=v1', '-z', '--untracked-files=all'],
            ['git', '-c', 'safe.directory=' . $this->repositoryRoot, 'show-ref'],
            ['git', '-c', 'safe.directory=' . $this->repositoryRoot, 'rev-parse', 'HEAD'],
        ] as $command) {
            $result = $this->process($command);
            $this->require(0 === $result->exitCode);
            $parts[] = $result->stdout;
        }
        foreach (['.git/config', 'composer.lock'] as $path) {
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
        $joined = implode('', $this->observedStreams);
        $forbidden = [$this->repositoryRoot, str_replace('/', '\\', $this->repositoryRoot), 'private-contribution-sentinel', 'KONGONO_PHASE12_FIXTURE_SECRET', 'AcceptanceApplicationExtension', 'AcceptanceOperationExtension', '<?php'];
        foreach ($forbidden as $value) {
            if ('' !== $value && str_contains($joined, $value)) {
                return false;
            }
        }

        return true;
    }

    private function assertNoDisclosure(): void
    {
        $this->require($this->streamsAreSafe());
    }

    private function safeFailures(): string
    {
        return implode('', $this->observedStreams);
    }

    /** @param callable(): mixed $operation */
    private function expectFailure(callable $operation): void
    {
        try {
            $operation();
        } catch (Throwable $failure) {
            $message = (string) $failure;
            $this->require(!str_contains($message, $this->repositoryRoot));

            return;
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

    private static function successBytes(): string
    {
        return json_encode(['status' => 'ok', 'profile' => 'kongono-phase12-extension-platform', 'case_count' => self::REQUIRED_CASES, 'cleanup' => 'complete'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
