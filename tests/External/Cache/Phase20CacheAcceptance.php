<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Cache;

use Kongono\CacheAdapterContract\Adapter\CacheAdapter;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterIdentity;
use Kongono\CacheAdapterContract\Adapter\CacheAdapterLimits;
use Kongono\CacheAdapterContract\AdapterContractVersion;
use Kongono\CacheAdapterContract\Request\CacheAdapterLookupRequest as AdapterLookupRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterRemoveRequest as AdapterRemoveRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterReplaceRequest as AdapterReplaceRequest;
use Kongono\CacheAdapterContract\Request\CacheAdapterStoreRequest as AdapterStoreRequest;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupResult;
use Kongono\CacheAdapterContract\Result\CacheAdapterLookupStatus;
use Kongono\CacheAdapterContract\Result\CacheAdapterMutationResult;
use Kongono\CacheAdapterMemory\Adapter\BoundedMemoryCacheAdapter;
use Kongono\CacheAdapterMemory\Configuration\MemoryAdapterCapacity;
use Kongono\CacheAdapterMemory\Configuration\MemoryEvictionPolicy;
use Kongono\CacheApplication\Definition\CacheApplicationDefinition;
use Kongono\CacheApplication\Mapping\CacheProfileCodecMapping;
use Kongono\CacheApplication\Runtime\CacheApplicationRuntimeFactory;
use Kongono\CacheApplication\Source\CacheProfileSourceLoader;
use Kongono\CacheApplication\Workflow\CacheApplicationWorkflow;
use Kongono\CacheApplication\Workflow\CurrentCacheApplicationBuild;
use Kongono\CacheBuild\Artifact\CacheProfileArtifactPublisher;
use Kongono\CacheBuild\Artifact\CacheProfileArtifactValidator;
use Kongono\CacheBuild\Build\CacheProfileBuilder;
use Kongono\CacheBuild\Profile\CacheProfileDeclaration;
use Kongono\CacheBuild\Profile\CacheProfileName;
use Kongono\CacheBuild\Profile\CacheProfileSet;
use Kongono\CacheBuild\Profile\CacheProfileValueLimit;
use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Freshness\CacheFreshnessWindow;
use Kongono\CacheContract\Freshness\CacheLifetime;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Outcome\CacheLookupOutcome;
use Kongono\CacheContract\Outcome\CacheMutationOutcome;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;
use Kongono\CacheInspection\Derivation\CacheIntelligenceDeriver;
use Kongono\CacheInspection\Inspection\CacheInspectionRequest;
use Kongono\CacheInspection\Inspection\CacheInspector;
use Kongono\CacheInspection\Projection\CacheObservationProjector;
use Kongono\CacheInspection\Projection\CacheSecurityAuditProjector;
use Kongono\CacheInspection\Role\CacheInspectionDeveloperRole;
use Kongono\CacheInspection\RuntimeEvidence\DetachedCacheRuntimeEvidence;
use Kongono\CacheInspection\Verification\CacheIntelligenceVerdict;
use Kongono\CacheInspection\Verification\CacheIntelligenceVerifier;
use Kongono\CacheRuntime\Operation\CacheLookupRequest;
use Kongono\CacheRuntime\Operation\CacheRemoveRequest;
use Kongono\CacheRuntime\Operation\CacheReplaceRequest;
use Kongono\CacheRuntime\Operation\CacheStoreRequest;
use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandOutputPort;
use Kongono\Observability\ObservabilityContext;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

final class Phase20CacheAcceptance
{
    private const int STREAM_LIMIT = 1_048_576;
    private const int PROCESS_TIMEOUT_SECONDS = 180;
    private const string FAILURE = "Kongono Phase 20 external acceptance failed.\n";
    private const string SUCCESS = "{\"schema\":\"kongono.phase-20-cache-acceptance\",\"version\":1,\"phase\":\"20\",\"profile\":\"cache-platform-local/1\",\"status\":\"accepted\",\"checks\":[\"contract\",\"build-currentness\",\"publication\",\"memory-adapter\",\"runtime-isolation\",\"application-parity\",\"inspection\",\"nondisclosure\",\"cleanup\"],\"check_count\":9}\n";
    private int $caseCount = 0;

    public function __construct(private readonly string $repositoryRoot) {}

    /**
     * @param list<string> $arguments
     * @param resource     $stdout
     * @param resource     $stderr
     */
    public function run(array $arguments, $stdout, $stderr): int
    {
        try {
            if (['--child-success'] === $arguments) {
                return $this->successChild($stdout);
            }
            if (['--child-failure'] === $arguments) {
                $workspace = new TemporaryCacheWorkspace($this->repositoryRoot);
                try {
                    throw new RuntimeException(self::privateLiteral());
                } finally {
                    $workspace->cleanup();
                }
            }
            if (['--child-malformed'] === $arguments) {
                fwrite($stdout, "{}\n");

                return 0;
            }
            if (['--child-overflow'] === $arguments) {
                fwrite($stdout, str_repeat('x', self::STREAM_LIMIT + 1));

                return 0;
            }
            if (['--child-timeout'] === $arguments) {
                sleep(5);

                return 0;
            }
            if (['--internal-count'] === $arguments) {
                $result = $this->child('--child-success');
                $count = $this->internalCount($result);
                fwrite($stdout, (string) $count . "\n");

                return 0;
            }
            if ([] !== $arguments) {
                throw new RuntimeException('phase20_mode_failed');
            }

            $before = $this->repositoryFingerprint();
            $residue = $this->temporaryResidue();
            $single = $this->child('--child-success');
            $later = $this->child('--child-success');
            [$concurrentA, $concurrentB] = $this->childPair();
            $failure = $this->child('--child-failure');
            $malformed = $this->child('--child-malformed');
            $this->require($this->fails(fn() => $this->child('--child-overflow')));
            $this->require($this->fails(fn() => $this->child('--child-timeout', 1)));
            $counts = [
                $this->internalCount($single),
                $this->internalCount($later),
                $this->internalCount($concurrentA),
                $this->internalCount($concurrentB),
            ];
            $this->require(1 === count(array_unique($counts, SORT_REGULAR)) && $counts[0] > 0);
            $this->require(0 !== $failure->exitCode && '' === $failure->stdout && self::FAILURE === $failure->stderr);
            $this->require(0 === $malformed->exitCode && "{}\n" === $malformed->stdout && '' === $malformed->stderr);
            $this->require(!str_contains($failure->stderr, self::privateLiteral()));
            $this->require($before === $this->repositoryFingerprint());
            $this->require($residue === $this->temporaryResidue());
            fwrite($stdout, self::SUCCESS);

            return 0;
        } catch (Throwable) {
            fwrite($stderr, self::FAILURE);

            return 1;
        }
    }

    /** @param resource $stdout */
    private function successChild($stdout): int
    {
        $workspace = new TemporaryCacheWorkspace($this->repositoryRoot);
        try {
            $current = $this->contractBuildAndPublication($workspace);
            $this->memoryAdapter();
            $this->runtime($current);
            $commandEvidence = $this->applicationAndCommands($workspace, $current);
            $inspectionEvidence = $this->inspection($current);
            $this->nondisclosure($commandEvidence . $inspectionEvidence);
            $this->case($this->caseCount > 0);
            $bytes = json_encode(['case_count' => $this->caseCount], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
            $this->case(!str_contains($bytes, self::privateLiteral()));
        } finally {
            $workspace->cleanup();
        }
        fwrite($stdout, json_encode(['case_count' => $this->caseCount], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");

        return 0;
    }

    private function contractBuildAndPublication(TemporaryCacheWorkspace $workspace): CurrentCacheApplicationBuild
    {
        $profile = self::profile();
        $set = new CacheProfileSet([$profile]);
        $first = (new CacheProfileBuilder())->build($set);
        $second = (new CacheProfileBuilder())->build(new CacheProfileSet([$profile]));
        $this->case($first->artifact()->bytes() === $second->artifact()->bytes());
        $this->case($first->buildIdentity()->value() === $second->buildIdentity()->value());
        $this->case($first->artifact()->artifactIdentity()->value() === $second->artifact()->artifactIdentity()->value());
        $validated = (new CacheProfileArtifactValidator())->validateCurrent($set, $first->artifact()->bytes());
        $this->case($validated->equals($first->artifact()));

        foreach ([
            static fn() => new CacheNamespace(''),
            static fn() => new LogicalCacheKey(''),
            static fn() => new CacheLifetime(0),
            static fn() => new CacheProfileValueLimit(1_048_577),
            static fn() => (new CacheProfileSourceLoader())->load(''),
            static fn() => (new CacheProfileSourceLoader())->load('{}'),
            static fn() => (new CacheProfileSourceLoader())->load('{'),
            static fn() => (new CacheProfileSourceLoader())->load(str_replace('"contract_version":1', '"contract_version":2', self::sourceBytes())),
            static fn() => (new CacheProfileArtifactValidator())->validateStructure(str_replace('"version":1', '"version":2', $first->artifact()->bytes())),
            static fn() => (new CacheProfileArtifactValidator())->validateStructure(str_replace("}\n", ',"extra":true}' . "\n", $first->artifact()->bytes())),
            static fn() => (new CacheProfileArtifactValidator())->validateCurrent(new CacheProfileSet([self::profile(2_000_000)]), $first->artifact()->bytes()),
        ] as $operation) {
            $this->case($this->fails($operation));
        }

        $root = $workspace->directory('publication');
        $publisher = new CacheProfileArtifactPublisher();
        $publication = $publisher->publish($first->artifact(), $root);
        $again = $publisher->publish($first->artifact(), $root);
        $path = $root . '/' . $publication->relativeArtifactPath();
        $this->case(!$publication->identicalContentExisted() && $again->identicalContentExisted());
        $this->case($publication->relativeArtifactPath() === $again->relativeArtifactPath());
        $this->case(is_file($path) && !is_link($path));
        $this->case($first->artifact()->bytes() === file_get_contents($path));
        $this->case(!str_contains($publication->relativeArtifactPath(), '..'));
        $this->case([] === glob($root . '/objects/sha256/*/.*.tmp', GLOB_NOSORT));
        $original = (string) file_get_contents($path);
        $this->case(strlen('changed') === file_put_contents($path, 'changed'));
        try {
            $this->case($this->fails(fn() => $publisher->publish($first->artifact(), $root)));
        } finally {
            $this->case(strlen($original) === file_put_contents($path, $original));
        }

        return new CurrentCacheApplicationBuild($set, $first->artifact());
    }

    private function memoryAdapter(): void
    {
        $adapter = new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(2, 8, 4), MemoryEvictionPolicy::OldestInsertionFirst);
        $one = self::entry('one', '1111');
        $two = self::entry('two', '2222');
        $three = self::entry('three', '3333');
        $this->case(CacheAdapterLookupStatus::Miss === $adapter->lookup(new AdapterLookupRequest($one->keyIdentity()))->status());
        $stored = $adapter->store(new AdapterStoreRequest($one));
        $evidence = $stored->entryEvidence();
        $this->case(CacheMutationOutcome::Stored === $stored->outcome() && null !== $evidence);
        $detached = $evidence;
        $this->case(CacheMutationOutcome::Conflict === $adapter->store(new AdapterStoreRequest(self::entry('one', 'xxxx')))->outcome());
        $wrong = new CacheEntryVersion(str_repeat('a', 64));
        $this->case(CacheMutationOutcome::Conflict === $adapter->replace(new AdapterReplaceRequest(self::entry('one', 'zzzz'), $wrong))->outcome());
        $this->case('1111' === $adapter->lookup(new AdapterLookupRequest($one->keyIdentity()))->entryEvidence()?->valueBytes()->bytes());
        $replaced = $adapter->replace(new AdapterReplaceRequest(self::entry('one', 'aaaa'), $evidence?->entryVersion() ?? $wrong));
        $replacement = $replaced->entryEvidence();
        $this->case(CacheMutationOutcome::Stored === $replaced->outcome() && null !== $replacement);
        $adapter->store(new AdapterStoreRequest($two));
        $adapter->store(new AdapterStoreRequest($three));
        $this->case(CacheAdapterLookupStatus::Miss === $adapter->lookup(new AdapterLookupRequest($one->keyIdentity()))->status());
        $this->case('1111' === $detached?->valueBytes()->bytes());
        $this->case(CacheMutationOutcome::Removed === $adapter->remove(new AdapterRemoveRequest($two->keyIdentity(), $adapter->lookup(new AdapterLookupRequest($two->keyIdentity()))->entryEvidence()?->entryVersion() ?? $wrong))->outcome());
        $this->case(CacheMutationOutcome::NotFound === $adapter->remove(new AdapterRemoveRequest($two->keyIdentity(), $wrong))->outcome());
        $independent = new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(2, 8, 4), MemoryEvictionPolicy::OldestInsertionFirst);
        $this->case(CacheAdapterLookupStatus::Miss === $independent->lookup(new AdapterLookupRequest($three->keyIdentity()))->status());
    }

    private function runtime(CurrentCacheApplicationBuild $current): void
    {
        $mapping = new CacheProfileCodecMapping(new CacheProfileName('application'), new CacheCodecIdentity('application-json', 1));
        $adapter = new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(4, 4096, 1024), MemoryEvictionPolicy::OldestInsertionFirst);
        $factory = new CacheApplicationRuntimeFactory();
        $runtime = $factory->create($current, $mapping, $adapter->identity(), $adapter);
        $key = new LogicalCacheKey('private-phase20-key');
        $this->case(CacheLookupOutcome::Miss === $runtime->lookup(new CacheLookupRequest($key, new CacheInstant(100)))->outcome());
        $stored = $runtime->store(new CacheStoreRequest($key, new CacheValueBytes(self::privateLiteral()), new CacheInstant(100)));
        $storedEvidence = $stored->entryEvidence();
        $this->case(CacheMutationOutcome::Stored === $stored->outcome() && null !== $storedEvidence);
        $this->case(CacheLookupOutcome::FreshHit === $runtime->lookup(new CacheLookupRequest($key, new CacheInstant(101)))->outcome());
        $this->case(CacheLookupOutcome::Expired === $runtime->lookup(new CacheLookupRequest($key, new CacheInstant(1_000_101)))->outcome());
        $wrong = new CacheEntryVersion(str_repeat('b', 64));
        $this->case(CacheMutationOutcome::Conflict === $runtime->replace(new CacheReplaceRequest($key, new CacheValueBytes('replacement'), $wrong, new CacheInstant(2_000_000)))->outcome());
        $replaced = $runtime->replace(new CacheReplaceRequest($key, new CacheValueBytes('replacement'), $storedEvidence?->entryVersion() ?? $wrong, new CacheInstant(2_000_000)));
        $this->case(CacheMutationOutcome::Stored === $replaced->outcome());
        $this->case(CacheMutationOutcome::Removed === $runtime->remove(new CacheRemoveRequest($key, $replaced->entryEvidence()?->entryVersion() ?? $wrong, new CacheInstant(2_000_001)))->outcome());
        $this->case(CacheLookupOutcome::Miss === $runtime->lookup(new CacheLookupRequest($key, new CacheInstant(2_000_002)))->outcome());
        $replacement = new BoundedMemoryCacheAdapter(new MemoryAdapterCapacity(4, 4096, 1024), MemoryEvictionPolicy::OldestInsertionFirst);
        $isolated = $factory->create($current, $mapping, $replacement->identity(), $replacement);
        $this->case(CacheLookupOutcome::Miss === $isolated->lookup(new CacheLookupRequest($key, new CacheInstant(100)))->outcome());

        foreach ([false, true] as $unavailable) {
            $closed = self::closedAdapter($unavailable);
            $closedRuntime = $factory->create($current, $mapping, $closed->identity(), $closed);
            $expectedLookup = $unavailable ? CacheLookupOutcome::Unavailable : CacheLookupOutcome::Rejected;
            $expectedMutation = $unavailable ? CacheMutationOutcome::Unavailable : CacheMutationOutcome::Rejected;
            $this->case($expectedLookup === $closedRuntime->lookup(new CacheLookupRequest(new LogicalCacheKey('closed'), new CacheInstant(1)))->outcome());
            $this->case($expectedMutation === $closedRuntime->store(new CacheStoreRequest(new LogicalCacheKey('closed'), new CacheValueBytes('x'), new CacheInstant(1)))->outcome());
        }

        $failing = self::failingAdapter();
        $failingRuntime = $factory->create($current, $mapping, $failing->identity(), $failing);
        $this->case($this->fails(fn() => $failingRuntime->lookup(new CacheLookupRequest(new LogicalCacheKey('failure'), new CacheInstant(1)))));
        $failing->fail = false;
        $this->case(CacheLookupOutcome::Miss === $failingRuntime->lookup(new CacheLookupRequest(new LogicalCacheKey('failure'), new CacheInstant(1)))->outcome());
    }

    private function applicationAndCommands(TemporaryCacheWorkspace $workspace, CurrentCacheApplicationBuild $expected): string
    {
        $root = $workspace->directory('application');
        $output = $workspace->directory('application/var/cache');
        $workspace->write('application/kongono.cache.json', self::sourceBytes());
        $definition = new CacheApplicationDefinition($root, 'kongono.cache.json', $output);
        $workflow = new CacheApplicationWorkflow();
        $before = self::directoryIdentity($output);
        $plan = $workflow->plan($definition);
        $this->case($plan->publicationRequired());
        $this->case($before === self::directoryIdentity($output));
        $current = $workflow->build($definition);
        $published = self::directoryIdentity($output);
        $this->case($expected->artifact()->bytes() === $current->artifact()->bytes());
        $this->case($current->artifact()->bytes() === $workflow->build($definition)->artifact()->bytes());
        $this->case($published === self::directoryIdentity($output));
        $this->case($current->artifact()->bytes() === $workflow->check($definition)->artifact()->bytes());
        $this->case(!$workflow->plan($definition)->publicationRequired());

        $inputs = ['--application-root=' . $root, '--output-directory=' . $output, '--source-file=kongono.cache.json'];
        [$planStatus, $planOut] = self::command(['--json', 'cache:plan', ...$inputs]);
        [$buildStatus, $buildOut] = self::command(['--json', 'cache:build', '--apply', ...$inputs]);
        [$checkStatus, $checkOut] = self::command(['--json', 'cache:check', ...$inputs]);
        $this->case(0 === $planStatus && 0 === $buildStatus && 0 === $checkStatus);
        $this->case(str_contains($planOut, $plan->artifactIdentity()));
        $this->case(str_contains($buildOut, $current->artifact()->artifactIdentity()->value()));
        $this->case(str_contains($checkOut, $current->artifact()->artifactIdentity()->value()));
        $this->case($published === self::directoryIdentity($output));
        $this->case(!str_contains($planOut . $buildOut . $checkOut, $root));

        $skeleton = $workspace->copyTree($this->repositoryRoot . '/skeletons/cache-minimal', 'slice');
        require $skeleton . '/src/CacheWorkflow.php';
        $slice = new \CacheMinimalApp\CacheWorkflow($skeleton);
        $this->case($slice->plan()->publicationRequired());
        $sliceCurrent = $slice->build();
        $this->case($sliceCurrent->artifact()->bytes() === $slice->check()->artifact()->bytes());
        $this->case(!$slice->plan()->publicationRequired());

        return $planOut . $buildOut . $checkOut;
    }

    private function inspection(CurrentCacheApplicationBuild $current): string
    {
        $counts = array_fill_keys(DetachedCacheRuntimeEvidence::OUTCOMES, 1);
        $runtime = new DetachedCacheRuntimeEvidence([
            'profile_set_sha256' => $current->profiles()->identity()->value(),
            'build_sha256' => $current->artifact()->buildIdentity()->value(),
            'artifact_sha256' => $current->artifact()->artifactIdentity()->value(),
        ], $counts);
        $request = new CacheInspectionRequest($current, $runtime);
        $inspection = (new CacheInspector())->inspect($request);
        $this->case('supplied' === $inspection->runtimeEvidenceStatus());
        $this->case(11 === $inspection->count('outcome_total'));
        $this->case(!$inspection->flag('runtime_observed'));
        $bytes = $inspection->canonicalBytes();
        foreach (CacheInspectionDeveloperRole::cases() as $role) {
            $view = (new CacheIntelligenceDeriver())->derive($inspection, $role);
            $context = ObservabilityContext::uncorrelated();
            $observation = (new CacheObservationProjector())->project($inspection, $context);
            $audit = (new CacheSecurityAuditProjector())->project($inspection, $context);
            $verification = (new CacheIntelligenceVerifier())->verify($request, $inspection, $view, $observation, $audit);
            $this->case('not-established' === $view->graphEvidence()->relationship());
            $this->case($role->purpose() === $view->purpose());
            $this->case(str_contains($view->canonicalBytes(), '"role_execution":"not-performed"'));
            $this->case(CacheIntelligenceVerdict::Pass === $verification->verdict());
            $this->case('constructed-not-recorded' === self::attribute($observation->attributes(), 'inspection.projection'));
            $this->case('constructed-not-recorded' === self::attribute($audit->attributes(), 'inspection.projection'));
            $bytes .= $view->canonicalBytes();
        }
        $bindings = $runtime->bindings();
        $bindings['artifact_sha256'] = str_repeat('f', 64);
        $this->case($this->fails(fn() => (new CacheInspector())->inspect(new CacheInspectionRequest($current, new DetachedCacheRuntimeEvidence($bindings, $counts)))));

        return $bytes;
    }

    private function nondisclosure(string $evidence): void
    {
        foreach ([self::privateLiteral(), 'credential', 'token=', 'backend-address', (string) getenv('USERNAME')] as $forbidden) {
            if ('' !== $forbidden) {
                $this->case(!str_contains($evidence, $forbidden));
                $this->case(!str_contains(self::SUCCESS . self::FAILURE, $forbidden));
            }
        }
        try {
            new CacheNamespace(self::privateLiteral() . '/');
        } catch (Throwable $failure) {
            $this->case(!str_contains((string) $failure, self::privateLiteral()));
        }
    }

    private function internalCount(ProcessResult $result): int
    {
        $this->require(0 === $result->exitCode && '' === $result->stderr && str_ends_with($result->stdout, "\n"));
        $decoded = json_decode($result->stdout, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || ['case_count'] !== array_keys($decoded) || !is_int($decoded['case_count'])) {
            throw new RuntimeException('phase20_process_failed');
        }
        $count = $decoded['case_count'];
        $this->require($count > 0);

        return $count;
    }

    private function child(string $mode, int $timeout = self::PROCESS_TIMEOUT_SECONDS): ProcessResult
    {
        return $this->closeCaptured($this->openCaptured([PHP_BINARY, __DIR__ . '/run.php', $mode]), $timeout);
    }

    /** @return array{ProcessResult, ProcessResult} */
    private function childPair(): array
    {
        $first = $this->openCaptured([PHP_BINARY, __DIR__ . '/run.php', '--child-success']);
        $second = $this->openCaptured([PHP_BINARY, __DIR__ . '/run.php', '--child-success']);

        return [$this->closeCaptured($first), $this->closeCaptured($second)];
    }

    /**
     * @param list<string> $command
     * @return array{resource, TemporaryCacheWorkspace, string, string}
     */
    private function openCaptured(array $command): array
    {
        $workspace = new TemporaryCacheWorkspace($this->repositoryRoot);
        $stdoutPath = $workspace->write('stdout', '');
        $stderrPath = $workspace->write('stderr', '');
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['file', $stdoutPath, 'wb'], ['file', $stderrPath, 'wb']], $pipes, $this->repositoryRoot, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            $workspace->cleanup();
            throw new RuntimeException('phase20_process_failed');
        }
        fclose($pipes[0]);

        return [$process, $workspace, $stdoutPath, $stderrPath];
    }

    /** @param array{resource, TemporaryCacheWorkspace, string, string} $opened */
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
                throw new RuntimeException('phase20_process_failed');
            }
            $exit = $status['exitcode'] >= 0 ? $status['exitcode'] : $closed;

            return new ProcessResult($exit, $stdout, $stderr);
        } finally {
            $workspace->cleanup();
        }
    }

    private function repositoryFingerprint(): string
    {
        $result = $this->closeCaptured($this->openCaptured(['git', '-c', 'safe.directory=' . str_replace('\\', '/', $this->repositoryRoot), 'status', '--porcelain=v1', '--untracked-files=all']));
        $this->require(0 === $result->exitCode);

        return hash('sha256', $result->stdout);
    }

    private function temporaryResidue(): string
    {
        $paths = glob(rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/') . '/kongono-phase20-*', GLOB_NOSORT);
        if (false === $paths) {
            throw new RuntimeException('phase20_acceptance_failed');
        }
        sort($paths, SORT_STRING);

        return hash('sha256', implode("\n", $paths));
    }

    /**
     * @param list<string> $arguments
     * @return array{int, string}
     */
    private static function command(array $arguments): array
    {
        $out = self::port();
        $err = self::port();
        $status = (new KongoApplicationFactory())->create()->run(new KongoProcessRequest($arguments), $out, $err);

        return [$status, $out->bytes . $err->bytes];
    }

    /** @return CommandOutputPort&object{bytes: string} */
    private static function port(): CommandOutputPort
    {
        return new class implements CommandOutputPort {
            public string $bytes = '';

            public function write(string $bytes): void
            {
                $this->bytes .= $bytes;
            }
        };
    }

    private static function closedAdapter(bool $unavailable): CacheAdapter
    {
        return new class ($unavailable) implements CacheAdapter {
            public function __construct(private readonly bool $unavailable) {}
            public function protocolVersion(): AdapterContractVersion
            {
                return AdapterContractVersion::Version1;
            }
            public function identity(): CacheAdapterIdentity
            {
                return new CacheAdapterIdentity('closed-test', 1);
            }
            public function limits(): CacheAdapterLimits
            {
                return new CacheAdapterLimits(4, 4096, 1024);
            }
            public function lookup(AdapterLookupRequest $request): CacheAdapterLookupResult
            {
                return $this->unavailable ? CacheAdapterLookupResult::unavailable($request->keyIdentity()) : CacheAdapterLookupResult::rejected($request->keyIdentity());
            }
            public function store(AdapterStoreRequest $request): CacheAdapterMutationResult
            {
                return CacheAdapterMutationResult::withoutEvidence($this->unavailable ? CacheMutationOutcome::Unavailable : CacheMutationOutcome::Rejected, $request->entry()->keyIdentity());
            }
            public function replace(AdapterReplaceRequest $request): CacheAdapterMutationResult
            {
                return CacheAdapterMutationResult::withoutEvidence($this->unavailable ? CacheMutationOutcome::Unavailable : CacheMutationOutcome::Rejected, $request->entry()->keyIdentity());
            }
            public function remove(AdapterRemoveRequest $request): CacheAdapterMutationResult
            {
                return CacheAdapterMutationResult::withoutEvidence($this->unavailable ? CacheMutationOutcome::Unavailable : CacheMutationOutcome::Rejected, $request->keyIdentity());
            }
        };
    }

    private static function failingAdapter(): Phase20FailingCacheAdapter
    {
        return new Phase20FailingCacheAdapter();
    }

    private static function profile(int $lifetime = 1_000_000): CacheProfileDeclaration
    {
        return new CacheProfileDeclaration(
            ContractVersion::Version1,
            new CacheProfileName('application'),
            new CacheNamespace('application'),
            new CacheCodecIdentity('application-json', 1),
            new CacheLifetime($lifetime),
            new CacheProfileValueLimit(1024),
        );
    }

    private static function entry(string $key, string $bytes): \Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry
    {
        $value = new CacheValueBytes($bytes);

        return new \Kongono\CacheAdapterContract\Adapter\CacheAdapterEntry(
            ContractVersion::Version1,
            CacheKeyIdentity::from(new CacheNamespace('adapter-tests'), new LogicalCacheKey($key)),
            new CacheCodecIdentity('text', 1),
            $value,
            $value->contentIdentity(),
            new CacheFreshnessWindow(new CacheInstant(10), new CacheInstant(20)),
        );
    }

    private static function sourceBytes(): string
    {
        return "{\"schema\":\"kongono/cache-application-profile-source/1\",\"profiles\":[{\"name\":\"application\",\"contract_version\":1,\"namespace\":\"application\",\"codec\":{\"name\":\"application-json\",\"version\":1},\"lifetime_microseconds\":1000000,\"max_value_bytes\":1024}]}\n";
    }

    private static function directoryIdentity(string $root): string
    {
        $resolved = realpath($root);
        if (false === $resolved) {
            throw new RuntimeException('phase20_workspace_failed');
        }
        $root = str_replace('\\', '/', $resolved);
        $records = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $entry) {
            if (!$entry instanceof SplFileInfo || $entry->isLink() || !$entry->isFile()) {
                throw new RuntimeException('phase20_workspace_failed');
            }
            $path = str_replace('\\', '/', $entry->getPathname());
            $bytes = file_get_contents($path);
            if (false === $bytes) {
                throw new RuntimeException('phase20_workspace_failed');
            }
            $records[] = substr($path, strlen($root) + 1) . '|' . strlen($bytes) . '|' . hash('sha256', $bytes);
        }
        sort($records, SORT_STRING);

        return hash('sha256', implode("\n", $records));
    }

    private static function attribute(\Kongono\Observability\AttributeSet $attributes, string $key): bool|int|float|string|null
    {
        return $attributes->find(new \Kongono\Observability\AttributeKey($key))?->value();
    }

    private function fails(callable $operation): bool
    {
        try {
            $operation();
        } catch (Throwable $failure) {
            return !str_contains((string) $failure, self::privateLiteral());
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
            throw new RuntimeException('phase20_acceptance_failed');
        }
    }

    private static function privateLiteral(): string
    {
        return 'private-phase20-' . 'adversarial.example/secret';
    }
}

final class Phase20FailingCacheAdapter implements CacheAdapter
{
    public bool $fail = true;

    public function protocolVersion(): AdapterContractVersion
    {
        return AdapterContractVersion::Version1;
    }

    public function identity(): CacheAdapterIdentity
    {
        return new CacheAdapterIdentity('failure-test', 1);
    }

    public function limits(): CacheAdapterLimits
    {
        return new CacheAdapterLimits(4, 4096, 1024);
    }

    public function lookup(AdapterLookupRequest $request): CacheAdapterLookupResult
    {
        if ($this->fail) {
            throw new RuntimeException('private adapter message');
        }

        return CacheAdapterLookupResult::miss($request->keyIdentity());
    }

    public function store(AdapterStoreRequest $request): CacheAdapterMutationResult
    {
        throw new RuntimeException('private adapter message');
    }

    public function replace(AdapterReplaceRequest $request): CacheAdapterMutationResult
    {
        throw new RuntimeException('private adapter message');
    }

    public function remove(AdapterRemoveRequest $request): CacheAdapterMutationResult
    {
        throw new RuntimeException('private adapter message');
    }
}
