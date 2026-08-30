<?php

declare(strict_types=1);

namespace Kongono\Tests\External\ApplicationGraph;

use Closure;
use FilesystemIterator;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifact;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactBuilder;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactLoader;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactPublisher;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use Kongono\ApplicationGraphBuild\Graph\AuthoritativeApplicationGraph;
use Kongono\ApplicationGraphComposition\Build\CompositionApplicationGraphBuilder;
use Kongono\ApplicationGraphComposition\Projection\CompositionIrProjector;
use Kongono\ApplicationGraphContract\Identity\NodeIdentity;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceDeriver;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole;
use Kongono\ApplicationGraphIntelligence\Verification\ApplicationGraphIntelligenceVerifier;
use Kongono\Chie\Chie;
use Kongono\Chie\ChieRequest;
use Kongono\Chie\ChieStatus;
use Kongono\Composition\Build\CompositionArtifact;
use Kongono\Composition\Build\CompositionArtifactBuilder;
use Kongono\Hantei\Hantei;
use Kongono\Hantei\HanteiVerdict;
use Kongono\Knowledge\Artifact\FrameworkKnowledgeArtifactGenerator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;
use Throwable;

/** Executes the closed real-process Phase 11 application-graph acceptance profile. */
final class Phase11ApplicationGraphAcceptance
{
    private const int FAILURE = 1;
    private const int MAX_STREAM_BYTES = 1_048_576;
    private const string FAILURE_BYTES = "phase11_acceptance_failed\n";
    private const string PRIVATE_FIXTURE = 'Phase11PrivateFixtureDoNotDisclose';
    private const string ENVIRONMENT_SECRET = 'Phase11EnvironmentSecretDoNotDisclose';

    private string $repositoryRoot = '';
    private int $caseCount = 0;
    private int $activeProcesses = 0;

    /** @var list<string> */
    private array $observedOutput = [];

    /**
     * @param array<array-key, mixed> $arguments
     * @param resource                $stdout
     * @param resource                $stderr
     */
    public function run(array $arguments, mixed $stdout, mixed $stderr): int
    {
        $temporary = null;
        $before = null;
        $failed = false;

        try {
            $this->require(array_is_list($arguments) && [] === $arguments, 'invalid_invocation');
            $this->require(is_resource($stdout) && is_resource($stderr), 'invalid_output_ports');
            $root = realpath(dirname(__DIR__, 3));
            if (false === $root || is_link($root) || !is_dir($root)) {
                throw new RuntimeException('repository_root_invalid');
            }
            $this->repositoryRoot = self::normalize($root);
            $before = $this->repositoryFingerprint();
            $temporary = TemporaryApplicationGraphWorkspace::create($this->repositoryRoot);
            $this->accept($temporary);
        } catch (Throwable) {
            $failed = true;
        } finally {
            if (null !== $temporary) {
                try {
                    $root = $temporary->root();
                    $temporary->cleanup();
                    $this->require(!file_exists($root) && !is_link($root), 'temporary_residue');
                    $this->caseCompleted();
                } catch (Throwable) {
                    $failed = true;
                }
            }
            if (null !== $before) {
                try {
                    $this->require($before === $this->repositoryFingerprint(), 'repository_fingerprint_changed');
                    $this->caseCompleted();
                } catch (Throwable) {
                    $failed = true;
                }
            }
        }

        if ($failed || $this->caseCount < 1 || 0 !== $this->activeProcesses) {
            $this->write($stderr, self::FAILURE_BYTES);

            return self::FAILURE;
        }

        $success = json_encode([
            'status' => 'ok',
            'profile' => 'kongono-phase11-application-graph',
            'case_count' => $this->caseCount,
            'cleanup' => 'complete',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        $this->write($stdout, $success);

        return 0;
    }

    private function accept(TemporaryApplicationGraphWorkspace $temporary): void
    {
        foreach ($temporary->fixtureFiles() as $fixture) {
            require_once $fixture;
        }

        [$composition, $graph] = $this->acceptCompositionAndGraph($temporary);
        $artifact = $this->acceptCanonicalArtifact($composition, $graph);
        $path = $this->acceptPublicationAndLoading($temporary, $artifact, $graph);
        $this->acceptArtifactFailures($temporary, $artifact, $path, $graph);
        $this->acceptInspectionAndConsole($temporary, $artifact, $path);
        $this->acceptDeveloperIntelligence($artifact);
        $this->acceptAuthorityIsolation();
        $this->acceptDisclosure($temporary);

        if ('1' !== getenv('KONGONO_PHASE11_ACCEPTANCE_CHILD')) {
            $this->acceptFailureCleanup();
            $this->acceptOwnFailureContract();
            $this->acceptRepeatabilityAndConcurrency();
        }

        $this->require(0 === $this->activeProcesses, 'child_process_residue');
        $this->caseCompleted();
    }

    /** @return array{CompositionArtifact, AuthoritativeApplicationGraph} */
    private function acceptCompositionAndGraph(TemporaryApplicationGraphWorkspace $temporary): array
    {
        $compositionBuilder = new CompositionArtifactBuilder();
        $composition = $compositionBuilder->build($temporary->applicationRoot());
        $graphBuilder = new CompositionApplicationGraphBuilder();
        $graph = $graphBuilder->build($composition);
        $repeat = $graphBuilder->build($composition);
        $this->require($this->graphBytes($graph) === $this->graphBytes($repeat), 'composition_graph_not_deterministic');
        $this->require($graph !== $repeat && $graph->nodes()[0] !== $repeat->nodes()[0], 'composition_graph_not_detached');
        $this->caseCompleted();

        $projection = (new CompositionIrProjector())->project($composition);
        $builder = new ApplicationGraphBuilder();
        $forward = $builder->build($projection->nodeRecords(), $projection->relationRecords());
        $reversed = $builder->build(array_reverse($projection->nodeRecords()), array_reverse($projection->relationRecords()));
        $this->require($this->graphBytes($forward) === $this->graphBytes($reversed), 'unordered_ir_changed_graph');
        $this->caseCompleted();

        $nodeIds = array_map(static fn($node): string => $node->identity()->value(), $graph->nodes());
        $relationIds = array_map(static fn($relation): string => $relation->identity()->value(), $graph->relations());
        $sortedNodes = $nodeIds;
        $sortedRelations = $relationIds;
        sort($sortedNodes, SORT_STRING);
        sort($sortedRelations, SORT_STRING);
        $this->require($nodeIds === $sortedNodes && $relationIds === $sortedRelations, 'graph_order_not_canonical');
        $this->caseCompleted();

        $nodes = $projection->nodeRecords();
        $relations = $projection->relationRecords();
        $this->expectThrowable(static fn() => $builder->build([$nodes[0], $nodes[0]], []), 'duplicate_node_accepted');
        $this->expectThrowable(static fn() => $builder->build([], [$relations[0]]), 'missing_relation_endpoint_accepted');
        $this->expectThrowable(static fn() => new NodeIdentity('NOT-A-DIGEST'), 'invalid_identity_accepted');

        $temporary->writeComposition(true);
        $reorderedComposition = $compositionBuilder->build($temporary->applicationRoot());
        $this->require($composition->modelSha256() === $reorderedComposition->modelSha256(), 'composition_semantic_order_changed');
        $this->require($composition->sourceSha256() !== $reorderedComposition->sourceSha256(), 'composition_raw_provenance_not_preserved');
        $temporary->writeComposition();
        $this->caseCompleted();

        return [$composition, $graph];
    }

    private function acceptCanonicalArtifact(CompositionArtifact $composition, AuthoritativeApplicationGraph $graph): ApplicationGraphArtifact
    {
        unset($composition);
        $builder = new ApplicationGraphArtifactBuilder();
        $first = $builder->build($graph);
        $second = $builder->build($graph);
        $this->require($first->canonicalBytes() === $second->canonicalBytes(), 'artifact_bytes_not_deterministic');
        $this->require($first->artifactSha256() === hash('sha256', $first->canonicalBytes()), 'artifact_identity_invalid');
        $this->require($first->graphIdentity()->value() === $second->graphIdentity()->value(), 'graph_identity_not_deterministic');
        $this->require(str_ends_with($first->canonicalBytes(), "\n") && !str_contains($first->canonicalBytes(), "\r"), 'artifact_line_contract_invalid');
        $this->caseCompleted();

        $inspection = $first->inspection();
        $this->require($inspection->nodeCount() === count($graph->nodes()), 'inspection_node_count_invalid');
        $this->require($inspection->relationCount() === count($graph->relations()), 'inspection_relation_count_invalid');
        $this->require($inspection->artifactSha256() === $first->artifactSha256(), 'inspection_artifact_identity_invalid');
        $this->caseCompleted();

        return $first;
    }

    private function acceptPublicationAndLoading(
        TemporaryApplicationGraphWorkspace $temporary,
        ApplicationGraphArtifact $artifact,
        AuthoritativeApplicationGraph $graph,
    ): string {
        $publisher = new ApplicationGraphArtifactPublisher();
        $path = $publisher->publish($artifact, $temporary->outputDirectory());
        $this->require(str_starts_with(self::normalize($path), $temporary->outputDirectory() . '/'), 'published_path_not_contained');
        $this->require([$this->basename($path)] === $temporary->outputFiles(), 'publication_residue_invalid');
        $this->require($path === $publisher->publish($artifact, $temporary->outputDirectory()), 'publication_not_idempotent');
        $this->caseCompleted();

        $loader = new ApplicationGraphArtifactLoader();
        $loaded = $loader->load($path);
        $current = $loader->loadCurrent($path, $graph);
        $this->require($artifact->canonicalBytes() === $loaded->canonicalBytes(), 'loaded_artifact_changed');
        $this->require($artifact->canonicalBytes() === $current->canonicalBytes(), 'current_artifact_changed');
        $this->caseCompleted();

        return $path;
    }

    private function acceptArtifactFailures(
        TemporaryApplicationGraphWorkspace $temporary,
        ApplicationGraphArtifact $artifact,
        string $path,
        AuthoritativeApplicationGraph $graph,
    ): void {
        $loader = new ApplicationGraphArtifactLoader();
        $invalidRoot = $temporary->path('invalid');
        $this->expectThrowable(fn() => $loader->load($invalidRoot . '/missing.json'), 'missing_artifact_accepted');

        $temporary->write($invalidRoot . '/malformed.json', "{malformed}\n");
        $this->expectThrowable(fn() => $loader->load($invalidRoot . '/malformed.json'), 'malformed_artifact_accepted');

        $temporary->write($invalidRoot . '/truncated.json', substr($artifact->canonicalBytes(), 0, -2));
        $this->expectThrowable(fn() => $loader->load($invalidRoot . '/truncated.json'), 'truncated_artifact_accepted');

        $temporary->write($invalidRoot . '/oversized.json', str_repeat('x', ApplicationGraphArtifactBuilder::MAX_ARTIFACT_BYTES + 1));
        $this->expectThrowable(fn() => $loader->load($invalidRoot . '/oversized.json'), 'oversized_artifact_accepted');

        $original = $temporary->read($path);
        $temporary->write($path, $original . "tampered\n");
        $this->expectThrowable(fn() => $loader->load($path), 'tampered_artifact_accepted');
        $temporary->write($path, $original);

        $different = (new ApplicationGraphBuilder())->build([], []);
        $this->expectThrowable(fn() => $loader->loadCurrent($path, $different), 'stale_artifact_accepted');
        $this->require($artifact->canonicalBytes() === $loader->loadCurrent($path, $graph)->canonicalBytes(), 'current_reload_failed');
        $this->caseCompleted();

        $alias = $temporary->path('output-alias');
        $linked = $this->createDirectoryAlias($temporary, $temporary->outputDirectory(), $alias);
        try {
            $this->expectThrowable(fn() => $loader->load($linked . '/' . $this->basename($path)), 'linked_artifact_accepted');
        } finally {
            $this->removeDirectoryAlias($temporary, $alias);
        }
        clearstatcache();
        $this->require(false === @lstat($alias) && false === @readlink($alias), 'linked_alias_residue');
        $this->caseCompleted();
    }

    private function acceptInspectionAndConsole(
        TemporaryApplicationGraphWorkspace $temporary,
        ApplicationGraphArtifact $artifact,
        string $path,
    ): void {
        $inspection = $artifact->inspection();
        $nodeTotal = 0;
        foreach (NodeType::cases() as $type) {
            $nodeTotal += $inspection->nodeTypeCount($type);
        }
        $relationTotal = 0;
        foreach (RelationType::cases() as $type) {
            $relationTotal += $inspection->relationTypeCount($type);
        }
        $this->require($inspection->nodeCount() === $nodeTotal && $inspection->relationCount() === $relationTotal, 'inspection_vocabulary_totals_invalid');
        $this->caseCompleted();

        $edge = $this->repositoryRoot . '/vendor/bin/kongo';
        $command = [PHP_BINARY, $edge, '--json', 'application-graph:inspect', '--artifact-path=' . $path, '--no-interaction'];
        $first = $this->process($command, $this->repositoryRoot);
        $second = $this->process($command, $this->repositoryRoot);
        $this->expectSuccess($first);
        $this->expectSuccess($second);
        $this->require($first->stdout() === $second->stdout(), 'console_machine_output_not_deterministic');
        $document = $this->canonicalJsonLine($first->stdout());
        $this->require('succeeded' === ($document['outcome'] ?? null), 'console_machine_outcome_invalid');
        $this->require('' === $first->stderr(), 'console_machine_stderr_not_empty');
        $this->observe($first);
        $this->observe($second);
        $this->caseCompleted();

        $human = $this->process([PHP_BINARY, $edge, 'application-graph:inspect', '--artifact-path=' . $path], $this->repositoryRoot);
        $this->expectSuccess($human);
        $this->require(str_ends_with($human->stdout(), "\n") && '' === $human->stderr(), 'console_human_stream_contract_invalid');
        $this->require(str_contains($human->stdout(), $artifact->artifactSha256()), 'console_human_identity_missing');
        $this->observe($human);
        $this->caseCompleted();

        $before = $temporary->treeIdentity();
        $failure = $this->process([PHP_BINARY, $edge, '--json', 'application-graph:inspect', '--artifact-path=' . $temporary->path('private-missing.json')], $this->repositoryRoot);
        $this->expectFailure($failure);
        $this->require(3 === $failure->exitCode() && '' === $failure->stderr(), 'console_failure_contract_invalid');
        $this->require(str_contains($failure->stdout(), 'KGN-CONSOLE-APPLICATION-1004'), 'console_failure_identity_missing');
        $this->require($before === $temporary->treeIdentity(), 'console_inspection_mutated_workspace');
        $this->observe($failure);
        $this->caseCompleted();
    }

    private function acceptDeveloperIntelligence(ApplicationGraphArtifact $artifact): void
    {
        $inspection = $artifact->inspection();
        $deriver = new ApplicationGraphIntelligenceDeriver();
        $views = [];
        foreach (DeveloperIntelligenceRole::cases() as $role) {
            $views[$role->value] = $deriver->derive($inspection, $role);
        }
        $neutral = [];
        foreach ($views as $view) {
            $array = $view->toArray();
            unset($array['role']);
            $neutral[] = $array;
        }
        $this->require($neutral[0] === $neutral[1] && $neutral[0] === $neutral[2], 'role_shared_facts_not_equal');
        $this->caseCompleted();

        $verifier = new ApplicationGraphIntelligenceVerifier();
        foreach ($views as $view) {
            $verification = $verifier->verify($inspection, $view);
            $this->require('pass' === $verification->verdict()->value && [] === $verification->failureTokens(), 'matching_role_evidence_rejected');
        }
        $this->caseCompleted();

        $stale = $this->corruptView($views['hantei'], ['artifactSha256' => str_repeat('f', 64)]);
        $wrongRole = $this->corruptView($views['hantei'], ['role' => DeveloperIntelligenceRole::Chie]);
        $unsupported = $this->corruptView($views['hantei'], ['currentness' => 'globally-current']);
        $this->require(DeveloperIntelligenceRole::Hantei !== $wrongRole->role(), 'wrong_role_guard_failed');
        $this->caseCompleted();
        foreach ([$stale, $unsupported] as $candidate) {
            $failure = $verifier->verify($inspection, $candidate);
            $this->require('fail' === $failure->verdict()->value && [] !== $failure->failureTokens(), 'mismatched_role_evidence_accepted');
            $encoded = json_encode($failure->toArray(), JSON_THROW_ON_ERROR);
            $this->require(!str_contains($encoded, str_repeat('f', 64)) && !str_contains($encoded, 'globally-current'), 'verification_disclosed_mismatch');
        }
        $this->caseCompleted();

        $this->acceptChieProjection($views['chie']);
        $this->acceptHanteiProjection($views['hantei']);
    }

    private function acceptChieProjection(ApplicationGraphIntelligenceView $view): void
    {
        $this->require(DeveloperIntelligenceRole::Chie === $view->role(), 'chie_projection_role_invalid');
        $knowledge = (new FrameworkKnowledgeArtifactGenerator())->generate($this->repositoryRoot);
        $response = (new Chie())->respondCurrent(
            $this->repositoryRoot,
            $knowledge->canonicalBytes(),
            ChieRequest::explain('developer-intelligence'),
        );
        $this->require(ChieStatus::Answered === $response->status(), 'chie_response_not_answered');
        $this->require([] !== $response->claims() && [] !== $response->claims()[0]->citations(), 'chie_evidence_not_cited');
        $encoded = json_encode($response->toArray(), JSON_THROW_ON_ERROR);
        $this->require(!str_contains($encoded, self::PRIVATE_FIXTURE) && !str_contains($encoded, $this->repositoryRoot), 'chie_disclosure_invalid');
        $this->caseCompleted();
    }

    private function acceptHanteiProjection(ApplicationGraphIntelligenceView $view): void
    {
        $this->require(DeveloperIntelligenceRole::Hantei === $view->role(), 'hantei_projection_role_invalid');
        $hostile = $this->repositoryRoot . '/missing-' . self::PRIVATE_FIXTURE;
        $report = (new Hantei())->verify($hostile);
        $encoded = json_encode($report->toArray(), JSON_THROW_ON_ERROR);
        $this->require(HanteiVerdict::Blocked === $report->verdict(), 'hantei_missing_subject_not_blocked');
        $this->require(!str_contains($encoded, $hostile) && !str_contains($encoded, self::PRIVATE_FIXTURE), 'hantei_disclosure_invalid');
        $this->caseCompleted();
    }

    private function acceptAuthorityIsolation(): void
    {
        foreach ([
            AuthoritativeApplicationGraph::class => ['version', 'nodes', 'relations'],
            ApplicationGraphIntelligenceDeriver::class => ['derive'],
            ApplicationGraphIntelligenceVerifier::class => ['verify'],
        ] as $class => $expected) {
            $reflection = new ReflectionClass($class);
            $methods = [];
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ('__construct' !== $method->getName() && $method->getDeclaringClass()->getName() === $class) {
                    $methods[] = $method->getName();
                }
            }
            sort($methods, SORT_STRING);
            sort($expected, SORT_STRING);
            $this->require($expected === $methods, 'authority_surface_changed');
            foreach ($methods as $method) {
                $this->require(!in_array($method, ['resolve', 'execute', 'dispatch', 'mutate', 'authorize', 'connect'], true), 'active_authority_exposed');
            }
        }
        $this->caseCompleted();

        $sources = '';
        foreach (['packages/application-graph-build/src', 'packages/application-graph-artifact/src', 'packages/application-graph-intelligence/src'] as $relative) {
            $sources .= $this->directoryPhpBytes($this->repositoryRoot . '/' . $relative);
        }
        foreach (['curl_' . 'init(', 'fsock' . 'open(', 'stream_socket_' . 'client(', 'shell_' . 'exec(', 'ev' . 'al(', 'new Container', 'serviceLocator'] as $forbidden) {
            $this->require(!str_contains($sources, $forbidden), 'prohibited_authority_token_present');
        }
        $this->caseCompleted();
    }

    private function acceptDisclosure(TemporaryApplicationGraphWorkspace $temporary): void
    {
        $allOutput = implode('', $this->observedOutput);
        foreach ([
            self::PRIVATE_FIXTURE,
            self::ENVIRONMENT_SECRET,
            $temporary->root(),
            str_replace('/', '\\', $temporary->root()),
            $this->repositoryRoot,
            str_replace('/', '\\', $this->repositoryRoot),
            get_current_user(),
            false !== gethostname() ? gethostname() : '',
            'Stack trace',
            'stack trace',
            "\033",
        ] as $protected) {
            $this->require('' === $protected || !str_contains($allOutput, $protected), 'protected_output_disclosed');
        }
        $this->caseCompleted();
    }

    private function acceptFailureCleanup(): void
    {
        $forced = null;
        $root = null;
        try {
            $forced = TemporaryApplicationGraphWorkspace::create($this->repositoryRoot);
            $root = $forced->root();
            throw new RuntimeException('forced_mid_run_failure');
        } catch (RuntimeException $failure) {
            $this->require('forced_mid_run_failure' === $failure->getMessage(), 'forced_failure_not_observed');
        } finally {
            if (null !== $forced) {
                $forced->cleanup();
            }
        }
        $this->require(null !== $root && !file_exists($root) && !is_link($root), 'forced_failure_residue');
        $this->caseCompleted();
    }

    private function acceptOwnFailureContract(): void
    {
        $failure = $this->process([PHP_BINARY, __DIR__ . '/run.php', 'unexpected'], $this->repositoryRoot, $this->childEnvironment());
        $this->require($failure->started() && 0 !== $failure->exitCode(), 'acceptance_failure_exit_invalid');
        $this->require('' === $failure->stdout() && self::FAILURE_BYTES === $failure->stderr(), 'acceptance_failure_streams_invalid');
        $this->caseCompleted();
    }

    private function acceptRepeatabilityAndConcurrency(): void
    {
        $command = [PHP_BINARY, __DIR__ . '/run.php'];
        $environment = $this->childEnvironment();
        $first = $this->process($command, $this->repositoryRoot, $environment);
        $second = $this->process($command, $this->repositoryRoot, $environment);
        $this->expectSuccess($first);
        $this->expectSuccess($second);
        $this->require($first->stdout() === $second->stdout() && '' === $first->stderr() && '' === $second->stderr(), 'repeated_runs_not_identical');
        $this->canonicalJsonLine($first->stdout());
        $this->caseCompleted();

        [$left, $right] = $this->processPair($command, $environment);
        $this->expectSuccess($left);
        $this->expectSuccess($right);
        $this->require($left->stdout() === $right->stdout() && '' === $left->stderr() && '' === $right->stderr(), 'concurrent_runs_not_identical');
        $this->caseCompleted();
    }

    /** @param array<string, mixed> $overrides */
    private function corruptView(ApplicationGraphIntelligenceView $valid, array $overrides): ApplicationGraphIntelligenceView
    {
        $reflection = new ReflectionClass(ApplicationGraphIntelligenceView::class);
        /** @var ApplicationGraphIntelligenceView $corrupt */
        $corrupt = $reflection->newInstanceWithoutConstructor();
        $array = $valid->toArray();
        $values = [
            'role' => $valid->role(),
            'artifactSha256' => $valid->artifactSha256(),
            'graphIdentity' => $valid->graphIdentity(),
            'contractVersion' => $valid->contractVersion(),
            'nodeCount' => $valid->nodeCount(),
            'relationCount' => $valid->relationCount(),
            'nodeTypeCounts' => $array['node_type_counts'],
            'relationTypeCounts' => $array['relation_type_counts'],
            'scope' => $valid->scope(),
            'visibility' => $valid->visibility(),
            'currentness' => $valid->currentness(),
            'authority' => $valid->authority(),
            'uncertainties' => $valid->uncertainties(),
        ];
        foreach ($overrides as $property => $value) {
            $values[$property] = $value;
        }
        foreach ($values as $property => $value) {
            $reflection->getProperty($property)->setValue($corrupt, $value);
        }

        return $corrupt;
    }

    private function graphBytes(AuthoritativeApplicationGraph $graph): string
    {
        return (new ApplicationGraphArtifactBuilder())->build($graph)->canonicalBytes();
    }

    private function createDirectoryAlias(TemporaryApplicationGraphWorkspace $temporary, string $target, string $alias): string
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $script = $temporary->path('create-junction.ps1');
            $temporary->write($script, <<<'POWERSHELL'
param([string] $Alias, [string] $Target)
$ErrorActionPreference = 'Stop'
$null = New-Item -ItemType Junction -Path $Alias -Target $Target
POWERSHELL
                . "\n");
            $result = $this->process(
                ['powershell.exe', '-NoLogo', '-NoProfile', '-NonInteractive', '-File', $script, $alias, $target],
                $this->repositoryRoot,
            );
            $this->require($result->started() && 0 === $result->exitCode(), 'junction_create_failed');
        } else {
            $this->require(@symlink($target, $alias), 'symlink_create_failed');
        }

        return $alias;
    }

    private function removeDirectoryAlias(TemporaryApplicationGraphWorkspace $temporary, string $alias): void
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $script = $temporary->path('remove-junction.ps1');
            $temporary->write($script, <<<'POWERSHELL'
param([string] $Alias)
$ErrorActionPreference = 'Stop'
[System.IO.Directory]::Delete($Alias)
POWERSHELL
                . "\n");
            $result = $this->process(
                ['powershell.exe', '-NoLogo', '-NoProfile', '-NonInteractive', '-File', $script, $alias],
                $this->repositoryRoot,
            );
            $this->require($result->started() && 0 === $result->exitCode(), 'junction_remove_failed');

            return;
        }
        $this->require(unlink($alias), 'symlink_remove_failed');
    }

    private function repositoryFingerprint(): string
    {
        $git = ['git', '-c', 'safe.directory=' . $this->repositoryRoot];
        $environment = $this->environment(['GIT_OPTIONAL_LOCKS' => '0']);
        $records = [];
        foreach ([
            [...$git, 'status', '--porcelain=v1', '-z', '--untracked-files=all'],
            [...$git, 'for-each-ref', '--format=%(refname)%00%(objectname)'],
            [...$git, 'rev-parse', 'HEAD', 'HEAD^{tree}'],
            [...$git, 'rev-list', '--count', 'HEAD'],
            [...$git, 'remote'],
            [...$git, 'tag'],
        ] as $command) {
            $result = $this->process($command, $this->repositoryRoot, $environment);
            $this->expectSuccess($result);
            $records[] = $result->stdout();
        }
        foreach (['.git/index', '.git/config', 'composer.lock'] as $path) {
            $bytes = file_get_contents($this->repositoryRoot . '/' . $path);
            if (false === $bytes) {
                throw new RuntimeException('repository_identity_read_failed');
            }
            $records[] = $path . "\0" . strlen($bytes) . "\0" . hash('sha256', $bytes);
        }
        $caller = [];
        foreach (['docs/dev_plan', 'docs/task_reports'] as $relative) {
            $root = $this->repositoryRoot . '/' . $relative;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
            /** @var SplFileInfo $entry */
            foreach ($iterator as $entry) {
                if (!$entry->isFile() || $entry->isLink()) {
                    throw new RuntimeException('caller_record_not_ordinary');
                }
                $path = self::normalize($entry->getPathname());
                $bytes = file_get_contents($path);
                if (false === $bytes) {
                    throw new RuntimeException('caller_record_read_failed');
                }
                $caller[] = substr($path, strlen($this->repositoryRoot) + 1) . "\0" . strlen($bytes) . "\0" . hash('sha256', $bytes);
            }
        }
        sort($caller, SORT_STRING);
        $records[] = implode("\n", $caller);

        return hash('sha256', implode("\0", $records));
    }

    /** @param list<string> $command
     *  @param array<string, string>|null $environment
     */
    private function process(array $command, string $workingDirectory, ?array $environment = null): ProcessResult
    {
        $pipes = [];
        ++$this->activeProcesses;
        $process = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $workingDirectory, $environment);
        if (!is_resource($process)) {
            --$this->activeProcesses;

            return new ProcessResult(false, null, '', '', 'process-start-failed');
        }
        try {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit = proc_close($process);
            $process = null;
            if (!is_string($stdout) || !is_string($stderr)) {
                return new ProcessResult(true, $exit, '', '', 'output-unavailable');
            }
            if (strlen($stdout) > self::MAX_STREAM_BYTES || strlen($stderr) > self::MAX_STREAM_BYTES) {
                return new ProcessResult(true, $exit, '', '', 'output-limit');
            }

            return new ProcessResult(true, $exit, $stdout, $stderr, null);
        } finally {
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
            --$this->activeProcesses;
        }
    }

    /** @param list<string> $command
     *  @param array<string, string> $environment
     *  @return array{ProcessResult, ProcessResult}
     */
    private function processPair(array $command, array $environment): array
    {
        $processes = [];
        foreach ([0, 1] as $index) {
            $pipes = [];
            ++$this->activeProcesses;
            $process = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->repositoryRoot, $environment);
            if (!is_resource($process)) {
                --$this->activeProcesses;
                throw new RuntimeException('concurrent_process_start_failed');
            }
            fclose($pipes[0]);
            $processes[$index] = [$process, $pipes];
        }

        $results = [];
        foreach ($processes as [$process, $pipes]) {
            try {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($process);
                $this->require(is_string($stdout) && is_string($stderr), 'concurrent_output_unavailable');
                $this->require(strlen($stdout) <= self::MAX_STREAM_BYTES && strlen($stderr) <= self::MAX_STREAM_BYTES, 'concurrent_output_limit');
                $results[] = new ProcessResult(true, $exit, $stdout, $stderr, null);
            } finally {
                --$this->activeProcesses;
            }
        }

        return [$results[0], $results[1]];
    }

    /** @return array<string, string> */
    private function childEnvironment(): array
    {
        return $this->environment([
            'KONGONO_PHASE11_ACCEPTANCE_CHILD' => '1',
            'KONGONO_PHASE11_UNRELATED_SECRET' => self::ENVIRONMENT_SECRET,
            'GIT_OPTIONAL_LOCKS' => '0',
        ]);
    }

    /** @param array<string, string> $overrides
     *  @return array<string, string>
     */
    private function environment(array $overrides): array
    {
        $environment = [];
        foreach (getenv() as $name => $value) {
            $environment[$name] = $value;
        }

        return [...$environment, ...$overrides];
    }

    private function expectThrowable(Closure $operation, string $failure): void
    {
        try {
            $operation();
        } catch (Throwable $exception) {
            $this->observedOutput[] = $exception->getMessage();
            $this->observedOutput[] = (string) $exception;
            $this->caseCompleted();

            return;
        }

        throw new RuntimeException($failure);
    }

    private function expectSuccess(ProcessResult $result): void
    {
        $this->require($result->started() && 0 === $result->exitCode() && null === $result->blockedReason(), 'process_success_expected');
    }

    private function expectFailure(ProcessResult $result): void
    {
        $this->require($result->started() && null !== $result->exitCode() && 0 !== $result->exitCode() && null === $result->blockedReason(), 'process_failure_expected');
    }

    private function observe(ProcessResult $result): void
    {
        $this->observedOutput[] = $result->stdout();
        $this->observedOutput[] = $result->stderr();
    }

    /** @return array<string, mixed> */
    private function canonicalJsonLine(string $bytes): array
    {
        $this->require(str_ends_with($bytes, "\n") && !str_contains($bytes, "\r"), 'json_line_contract_invalid');
        $decoded = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException('json_object_required');
        }
        $this->require(json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n" === $bytes, 'json_not_canonical');

        $object = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                throw new RuntimeException('json_object_required');
            }
            $object[$key] = $value;
        }

        return $object;
    }

    private function directoryPhpBytes(string $root): string
    {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            if ($entry->isFile() && 'php' === strtolower($entry->getExtension())) {
                $paths[] = $entry->getPathname();
            }
        }
        sort($paths, SORT_STRING);
        $bytes = '';
        foreach ($paths as $path) {
            $value = file_get_contents($path);
            $this->require(false !== $value, 'source_read_failed');
            $bytes .= $value;
        }

        return $bytes;
    }

    private function basename(string $path): string
    {
        return basename(str_replace('\\', '/', $path));
    }

    private function caseCompleted(): void
    {
        ++$this->caseCount;
    }

    private function require(bool $condition, string $failure): void
    {
        if (!$condition) {
            throw new RuntimeException($failure);
        }
    }

    /** @param resource $stream */
    private function write(mixed $stream, string $bytes): void
    {
        $written = fwrite($stream, $bytes);
        if ($written !== strlen($bytes)) {
            throw new RuntimeException('output_write_failed');
        }
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
