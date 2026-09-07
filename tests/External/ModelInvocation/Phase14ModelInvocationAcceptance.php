<?php

declare(strict_types=1);

namespace Kongono\Tests\External\ModelInvocation;

use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringGenerator;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringPublisher;
use Kongono\ModelInvocationApplication\Generated\ModelInvocationWiringValidator;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileFactory;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactBuilder;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactPublisher;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactValidator;
use Kongono\ModelInvocationBuild\Budget\CostLimit;
use Kongono\ModelInvocationBuild\Budget\InvocationExecutionBudget;
use Kongono\ModelInvocationBuild\Budget\UsageLimit;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\ContractVersion;
use Kongono\ModelInvocationContract\Cost\CurrencyCode;
use Kongono\ModelInvocationContract\Cost\DecimalAmount;
use Kongono\ModelInvocationContract\Input\InputMessage;
use Kongono\ModelInvocationContract\Input\InputRole;
use Kongono\ModelInvocationContract\Invocation\InvocationIdentity;
use Kongono\ModelInvocationContract\Invocation\InvocationLimits;
use Kongono\ModelInvocationContract\Invocation\InvocationRequest;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
use Kongono\ModelInvocationContract\Profile\ModelProfileReference;
use Kongono\ModelInvocationContract\Profile\ProfileIdentity;
use Kongono\ModelInvocationContract\Provider\ModelIdentity;
use Kongono\ModelInvocationContract\Provider\ProviderCapability;
use Kongono\ModelInvocationContract\Provider\ProviderCapabilitySet;
use Kongono\ModelInvocationContract\Provider\ProviderIdentity;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationContract\Receipt\ReceiptIdentity;
use Kongono\ModelInvocationContract\Result\FinishReason;
use Kongono\ModelInvocationContract\Result\ModelResult;
use Kongono\ModelInvocationContract\Result\ResultIdentity;
use Kongono\ModelInvocationContract\Result\ResultText;
use Kongono\ModelInvocationContract\Visibility\ModelVisibility;
use Kongono\ModelInvocationInspection\Derivation\ModelInvocationIntelligenceDeriver;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspectionRequest;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspector;
use Kongono\ModelInvocationInspection\Role\ModelInspectionDeveloperRole;
use Kongono\ModelInvocationInspection\Verification\ModelInvocationIntelligenceVerdict;
use Kongono\ModelInvocationInspection\Verification\ModelInvocationIntelligenceVerifier;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapter;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterEmission;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterSession;
use Kongono\ModelInvocationRuntime\Cancellation\InvocationCancellation;
use Kongono\ModelInvocationRuntime\Clock\InvocationMonotonicClock;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeRequest;
use Kongono\ModelInvocationRuntime\Runtime\ModelInvocationRuntime;
use RuntimeException;
use Throwable;

final class AcceptanceInvocationSession implements InvocationAdapterSession
{
    public int $nextCalls = 0;
    public int $closeCalls = 0;

    /** @param list<InvocationAdapterEmission|Throwable|null> $emissions */
    public function __construct(private array $emissions, private ?Throwable $closeFailure = null) {}

    public function next(): ?InvocationAdapterEmission
    {
        ++$this->nextCalls;
        $emission = array_shift($this->emissions);
        if ($emission instanceof Throwable) {
            throw $emission;
        }

        return $emission;
    }

    public function close(): void
    {
        ++$this->closeCalls;
        if (null !== $this->closeFailure) {
            throw $this->closeFailure;
        }
    }
}

final class AcceptanceInvocationAdapter implements InvocationAdapter
{
    public int $openCalls = 0;

    public function __construct(private AdapterIdentity $identity, private AcceptanceInvocationSession $session) {}

    public function identity(): AdapterIdentity
    {
        return $this->identity;
    }

    public function open(InvocationPlanSource $source): InvocationAdapterSession
    {
        unset($source);
        ++$this->openCalls;

        return $this->session;
    }
}

final class AcceptanceInvocationCancellation implements InvocationCancellation
{
    public int $calls = 0;

    /** @param non-empty-list<bool> $values */
    public function __construct(private array $values = [false]) {}

    public function isCancellationRequested(): bool
    {
        $value = $this->values[min($this->calls, count($this->values) - 1)];
        ++$this->calls;

        return $value;
    }
}

final class AcceptanceInvocationClock implements InvocationMonotonicClock
{
    public int $calls = 0;

    /** @param non-empty-list<int> $values */
    public function __construct(private array $values = [0]) {}

    public function milliseconds(): int
    {
        $value = $this->values[min($this->calls, count($this->values) - 1)];
        ++$this->calls;

        return $value;
    }
}

final class Phase14ModelInvocationAcceptance
{
    private const int STREAM_LIMIT = 1_048_576;
    private const int REQUIRED_CASES = 56;
    private const string FAILURE = "phase14_acceptance_failed\n";
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
                $workspace = new TemporaryModelInvocationWorkspace($this->repositoryRoot);
                try {
                    $this->accept($workspace);
                    $this->require(self::REQUIRED_CASES === $this->caseCount);
                } finally {
                    $workspace->cleanup();
                }
                fwrite($stdout, self::successBytes());

                return 0;
            }
            if (['--child-failure'] === $arguments) {
                $workspace = new TemporaryModelInvocationWorkspace($this->repositoryRoot);
                try {
                    throw new RuntimeException('private failure sentinel');
                } finally {
                    $workspace->cleanup();
                }
            }
            if ([] !== $arguments) {
                throw new RuntimeException('invalid mode');
            }

            $before = $this->repositoryFingerprint();
            $single = $this->child('--child-success');
            $failure = $this->child('--child-failure');
            $first = $this->child('--child-success');
            $second = $this->child('--child-success');
            [$concurrentA, $concurrentB] = $this->childPair();
            $expected = self::successBytes();
            $this->require(0 === $single->exitCode && $expected === $single->stdout && '' === $single->stderr);
            $this->require(0 !== $failure->exitCode && '' === $failure->stdout && self::FAILURE === $failure->stderr);
            $this->require($expected === $first->stdout && $first->stdout === $second->stdout);
            $this->require(0 === $concurrentA->exitCode && 0 === $concurrentB->exitCode && $concurrentA->stdout === $concurrentB->stdout && $expected === $concurrentA->stdout);
            $this->require($before === $this->repositoryFingerprint());
            fwrite($stdout, $expected);

            return 0;
        } catch (Throwable) {
            fwrite($stderr, self::FAILURE);

            return 1;
        }
    }

    private function accept(TemporaryModelInvocationWorkspace $workspace): void
    {
        $source = $this->source();
        $same = $this->source();
        $this->case($source->equals($same));
        $this->case(1 === count($source->request()->messages()));
        $this->case('provider.acceptance' === $source->request()->profile()->providerIdentity()->value());
        $this->case('model.acceptance' === $source->request()->profile()->modelIdentity()->value());
        $this->case($source->request()->profile()->capabilities()->supports(ProviderCapability::TextResult));
        $this->case(ModelVisibility::Private === $source->request()->messages()[0]->visibility());
        $this->case(ResultMode::Text === $source->request()->resultMode());
        $this->case(false === $source->request()->streaming());
        $this->case(1 === $source->budget()->maxAttempts());
        $this->case($this->fails(static fn(): InputMessage => new InputMessage(InputRole::User, ModelVisibility::Private, '')));
        $this->case($this->fails(static fn(): ProviderIdentity => new ProviderIdentity('INVALID')));
        $this->case($this->fails(static fn(): InvocationLimits => new InvocationLimits(0, 1, 0)));
        $this->case($this->fails(static fn(): ProviderCapabilitySet => new ProviderCapabilitySet([ProviderCapability::TextResult, ProviderCapability::TextResult])));
        $this->case($this->fails(static fn(): DecimalAmount => new DecimalAmount('01.0')));
        $this->case($source->request()->equals($same->request()));

        $builder = new InvocationPlanArtifactBuilder();
        $artifact = $builder->build($source);
        $artifactAgain = $builder->build($same);
        $validator = new InvocationPlanArtifactValidator();
        $this->case($artifact->canonicalBytes() === $artifactAgain->canonicalBytes());
        $this->case($artifact->artifactSha256() === hash('sha256', $artifact->canonicalBytes()));
        $this->case($validator->validateStructure($artifact->canonicalBytes())->artifactSha256() === $artifact->artifactSha256());
        $this->case($validator->validateCurrent($source, $artifact->canonicalBytes())->sourceSha256() === $artifact->sourceSha256());
        $this->case($this->fails(fn() => $validator->validateStructure(substr($artifact->canonicalBytes(), 0, -1))));
        $this->case($this->fails(fn() => $validator->validateCurrent($this->source('other'), $artifact->canonicalBytes())));
        $artifactDirectory = $workspace->directory('plans');
        $publisher = new InvocationPlanArtifactPublisher();
        $artifactPath = $publisher->publish($artifact, $artifactDirectory);
        $this->case(is_file($artifactPath));
        $this->case($artifactPath === $publisher->publish($artifact, $artifactDirectory));
        $this->case(!str_contains($artifact->canonicalBytes(), 'private-message-sentinel'));

        $result = $this->result($source);
        $session = new AcceptanceInvocationSession([InvocationAdapterEmission::result($result), null]);
        $adapter = new AcceptanceInvocationAdapter($source->adapterIdentity(), $session);
        $runtime = (new ModelInvocationRuntime())->run(new InvocationRuntimeRequest($source, $artifact->canonicalBytes(), new ReceiptIdentity('receipt.acceptance'), $adapter, new AcceptanceInvocationCancellation(), new AcceptanceInvocationClock([0, 1, 2, 3])));
        $this->case(InvocationOutcome::Succeeded === $runtime->receipt()->outcome());
        $this->case(InvocationState::Retired === $runtime->state());
        $this->case(1 === $runtime->receipt()->attemptCount());
        $this->case(1 === $adapter->openCalls);
        $this->case(1 === $session->closeCalls);
        $this->case($runtime->result()?->equals($result) ?? false);
        $this->case($runtime->receipt()->cleanupComplete());
        $cancelled = (new ModelInvocationRuntime())->run(new InvocationRuntimeRequest($source, $artifact->canonicalBytes(), new ReceiptIdentity('receipt.cancelled'), new AcceptanceInvocationAdapter($source->adapterIdentity(), new AcceptanceInvocationSession([])), new AcceptanceInvocationCancellation([true]), new AcceptanceInvocationClock()));
        $this->case(InvocationOutcome::Cancelled === $cancelled->receipt()->outcome());
        $this->case($this->fails(fn() => (new ModelInvocationRuntime())->run(new InvocationRuntimeRequest($source, $artifact->canonicalBytes(), new ReceiptIdentity('receipt.mismatch'), new AcceptanceInvocationAdapter(new AdapterIdentity('adapter.other'), new AcceptanceInvocationSession([])), new AcceptanceInvocationCancellation(), new AcceptanceInvocationClock()))));
        $this->case(0 === count($runtime->events()));

        $profile = (new ModelInvocationApplicationProfileFactory())->create($source, $artifact->canonicalBytes());
        $wiring = (new ModelInvocationWiringGenerator())->generate($profile);
        $wiringAgain = (new ModelInvocationWiringGenerator())->generate($profile);
        $wiringValidator = new ModelInvocationWiringValidator();
        $this->case($wiring->canonicalBytes() === $wiringAgain->canonicalBytes());
        $this->case($wiringValidator->validateStructure($wiring->canonicalBytes())->generatedWiringSha256() === $wiring->generatedWiringSha256());
        $this->case($wiringValidator->validateCurrent($profile, $wiring->canonicalBytes())->generatedWiringSha256() === $wiring->generatedWiringSha256());
        $this->case(!str_contains($wiring->canonicalBytes(), 'private-message-sentinel'));
        $wiringDirectory = $workspace->directory('wiring');
        $wiringPath = (new ModelInvocationWiringPublisher())->publish($workspace->root(), $wiringDirectory, $wiring);
        $this->case(is_file($wiringPath));
        $this->case($this->fails(fn() => $wiringValidator->validateStructure(substr($wiring->canonicalBytes(), 0, -1))));

        $inspection = (new ModelInvocationInspector())->inspect(new ModelInvocationInspectionRequest($profile, $wiring, $runtime));
        $inspectionAgain = (new ModelInvocationInspector())->inspect(new ModelInvocationInspectionRequest($profile, $wiring, $runtime));
        $this->case($inspection->canonicalBytes() === $inspectionAgain->canonicalBytes());
        $this->case(!str_contains($inspection->canonicalBytes(), 'private-message-sentinel'));
        $this->case($inspection->runtimeEvidenceSupplied());
        $this->case('model-graph-relationship-not-established' === (new ModelInvocationIntelligenceDeriver())->derive($inspection, ModelInspectionDeveloperRole::Chie)->graphEvidence()->relationship());
        $views = [];
        foreach (ModelInspectionDeveloperRole::cases() as $role) {
            $view = (new ModelInvocationIntelligenceDeriver())->derive($inspection, $role);
            $views[] = $view;
            $this->case('not-performed' === $view->roleExecution());
        }
        $verification = (new ModelInvocationIntelligenceVerifier())->verify($inspection, $views[0]);
        $this->case(ModelInvocationIntelligenceVerdict::Pass === $verification->verdict());
        $this->case('evidence-only' === $views[0]->authority());
        $this->case('not-performed' === $views[0]->repair());
        $this->case('none' === $views[0]->hiddenDataAccess());
        $this->case('not-performed' === $views[0]->providerInvocation());
        $this->case(!str_contains(json_encode($verification->toArray(), JSON_THROW_ON_ERROR), 'private-message-sentinel'));
        $this->case(!str_contains($artifact->canonicalBytes(), 'credential'));
        $this->case(!str_contains($wiring->canonicalBytes(), 'credential'));
        $this->case(!str_contains($inspection->canonicalBytes(), $workspace->root()));
    }

    private function source(string $suffix = 'acceptance'): InvocationPlanSource
    {
        $profile = new ModelProfileReference(ContractVersion::Version1, new ProfileIdentity('profile.' . $suffix), new ProviderIdentity('provider.' . $suffix), new ModelIdentity('model.' . $suffix), new ProviderCapabilitySet([ProviderCapability::TextResult, ProviderCapability::Streaming, ProviderCapability::UsageEvidence, ProviderCapability::CostEvidence]));
        $request = new InvocationRequest(ContractVersion::Version1, new InvocationIdentity('invocation.' . $suffix), $profile, [new InputMessage(InputRole::User, ModelVisibility::Private, 'private-message-sentinel')], ResultMode::Text, false, new InvocationLimits(1024, 8, 1));

        return new InvocationPlanSource(new InvocationPlanIdentity('plan.' . $suffix), new AdapterIdentity('adapter.' . $suffix), $request, new InvocationExecutionBudget(1000, 1, 2048, [new UsageLimit(new \Kongono\ModelInvocationContract\Usage\UsageMetricName('tokens'), new \Kongono\ModelInvocationContract\Usage\UsageUnitName('count'), 100)], new CostLimit(new CurrencyCode('USD'), new DecimalAmount('2.00'))));
    }

    private function result(InvocationPlanSource $source): ModelResult
    {
        return new ModelResult(ContractVersion::Version1, new ResultIdentity('result.acceptance'), $source->request()->identity(), $source->request()->profile(), ResultMode::Text, FinishReason::Completed, new ResultText('bounded-result'), null, null, null, ModelVisibility::Private);
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
            throw new RuntimeException('phase14_acceptance_failed');
        }
    }

    private function child(string $mode): ProcessResult
    {
        return $this->process([PHP_BINARY, __DIR__ . '/run.php', $mode]);
    }

    /** @return array{ProcessResult,ProcessResult} */
    private function childPair(): array
    {
        $a = $this->open([PHP_BINARY, __DIR__ . '/run.php', '--child-success']);
        $b = $this->open([PHP_BINARY, __DIR__ . '/run.php', '--child-success']);

        return [$this->close($a), $this->close($b)];
    }

    /** @param list<string> $command */
    private function process(array $command): ProcessResult
    {
        return $this->close($this->open($command));
    }

    /** @param list<string> $command
     * @return array{resource,array<int,resource>}
     */
    private function open(array $command): array
    {
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $this->repositoryRoot, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new RuntimeException('phase14_process_failed');
        }
        fclose($pipes[0]);

        return [$process, $pipes];
    }

    /** @param array{resource,array<int,resource>} $opened */
    private function close(array $opened): ProcessResult
    {
        [$process, $pipes] = $opened;
        $stdout = stream_get_contents($pipes[1], self::STREAM_LIMIT + 1);
        $stderr = stream_get_contents($pipes[2], self::STREAM_LIMIT + 1);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        $this->require(false !== $stdout && false !== $stderr && strlen($stdout) <= self::STREAM_LIMIT && strlen($stderr) <= self::STREAM_LIMIT);

        return new ProcessResult($exit, $stdout, $stderr);
    }

    private function repositoryFingerprint(): string
    {
        $result = $this->process(['git', '-c', 'safe.directory=' . str_replace('\\', '/', $this->repositoryRoot), 'status', '--porcelain=v1', '-uall']);
        $this->require(0 === $result->exitCode);

        return hash('sha256', $result->stdout);
    }

    private static function successBytes(): string
    {
        return "{\"status\":\"ok\",\"profile\":\"kongono-phase14-model-invocation\",\"case_count\":" . self::REQUIRED_CASES . ",\"cleanup\":\"complete\"}\n";
    }
}
