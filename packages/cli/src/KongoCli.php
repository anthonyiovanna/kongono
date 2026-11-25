<?php

declare(strict_types=1);

namespace Kongono\Cli;

use Closure;
use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\Eval\EvalCase;
use Kongono\Ai\Eval\EvalRunner;
use Kongono\Cli\Audit\SecurityAuditor;
use Kongono\Cli\Auth\AuthScaffolder;
use Kongono\Cli\Bridge\BridgeScaffolder;
use Kongono\Cli\Bridge\TypeScriptPropsGenerator;
use Kongono\Cli\Companions\ChieBuilder;
use Kongono\Cli\Companions\CompanionEvalSuite;
use Kongono\Cli\Companions\CompanionSessionStore;
use Kongono\Cli\Companions\DiffRenderer;
use Kongono\Cli\Companions\HanteiDebugger;
use Kongono\Cli\Companions\OshieTutor;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Database\MigrationLinter;
use Kongono\Cli\Deploy\DeployBlueprintGenerator;
use Kongono\Cli\Diagnostics\AppAutoFixer;
use Kongono\Cli\Diagnostics\IncidentReplayer;
use Kongono\Cli\Diagnostics\IncidentTestGenerator;
use Kongono\Cli\Diagnostics\KongonoAppInspector;
use Kongono\Cli\Eval\AgentCodingBenchmark;
use Kongono\Cli\Impact\AppSnapshot;
use Kongono\Cli\Impact\ImpactAnalyzer;
use Kongono\Cli\Lsp\KongonoLanguageServer;
use Kongono\Cli\Mcp\KongonoMcpFactory;
use Kongono\Cli\Perf\PerfProver;
use Kongono\Cli\Policy\PolicyVerifier;
use Kongono\Cli\Proof\MasterProver;
use Kongono\Cli\Sdk\SdkGenerator;
use Kongono\Cli\Security\TenantIsolationProver;
use Kongono\Cli\Shadow\ShadowDiffProver;
use Kongono\Cli\Skills\SkillsInstaller;
use Kongono\Cli\Ui\UiComponentGenerator;
use Kongono\Cli\Upgrade\UpgradeEngine;
use Kongono\Cli\Watch\WatchRunner;
use Kongono\Core\Cache\AtomicCacheInterface;
use Kongono\Core\Config;
use Kongono\Core\CoreException;
use Kongono\Core\Diagnostics\ErrorExplainer;
use Kongono\Core\Diagnostics\SecretScrubber;
use Kongono\Core\Graph\RouteNode;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Migrator;
use Kongono\Database\SeederInterface;
use Kongono\Http\App;
use Kongono\Http\OpenApi\OpenApiGenerator;
use Kongono\Jobs\Queue;
use Kongono\Jobs\QueueDriverInterface;
use Kongono\Jobs\Scheduler\Scheduler;
use Kongono\Jobs\Worker;
use Kongono\Security\Encryptor;
use Kongono\View\ViewEngine;
use Throwable;

/** Unified `kongo` CLI entry point (serve, routes, graph, oshie, chie, hantei). */
final readonly class KongoCli
{
    /**
     * @param (Closure(string): ?string)|null $promptReader Optional interactive prompt callback ($prompt) => line|null
     * @param (Closure(string): void)|null    $streamWriter Optional streaming delta writer (defaults to STDOUT)
     */
    public function __construct(
        private string $workingDirectory,
        private ?AiClient $aiOverride = null,
        private ?Closure $promptReader = null,
        private ?Closure $streamWriter = null,
    ) {}

    /**
     * Execute a CLI invocation and return [exitCode, output].
     *
     * @param list<string> $argv Arguments excluding script name
     * @return array{0: int, 1: string}
     */
    public function run(array $argv): array
    {
        $parsed = $this->parseArgs($argv);
        $command = $parsed['positional'][0] ?? 'help';
        $rest = array_slice($parsed['positional'], 1);
        $options = $parsed['options'];

        if (isset($options['help'])) {
            return [0, $this->helpText()];
        }
        if ('help' === $command && isset($options['version'])) {
            return [0, 'Kongono Framework v1.20.1 (PHP ' . PHP_VERSION . ")\n"];
        }

        $appRoot = $this->resolveAppRoot($options['app'] ?? null);

        try {
            return match ($command) {
                'help', '-h' => [0, $this->helpText()],
                'version', '-v' => [0, 'Kongono Framework v1.20.1 (PHP ' . PHP_VERSION . ")\n"],
                'routes' => $this->cmdRoutes($appRoot, $options),
                'graph' => $this->cmdGraph($appRoot),
                'openapi' => $this->cmdOpenApi($appRoot),
                'sdk:generate' => $this->cmdSdkGenerate($appRoot, $options),
                'bridge:init' => $this->cmdBridgeInit($appRoot, $rest, $options),
                'bridge:types' => $this->cmdBridgeTypes($appRoot, $options),
                'ui:add' => $this->cmdUiAdd($appRoot, $rest, $options),
                'auth:init' => $this->cmdAuthInit($appRoot, $options),
                'skills:install' => $this->cmdSkillsInstall($appRoot, $options),
                'mcp' => $this->cmdMcp($appRoot, $options),
                'lsp' => $this->cmdLsp($appRoot, $options),
                'audit' => $this->cmdAudit($appRoot, $options),
                'diagnose' => $this->cmdDiagnose($appRoot, $options),
                'prove' => $this->cmdProve($appRoot, $options),
                'policy:check' => $this->cmdPolicyCheck($appRoot, $options),
                'perf' => $this->cmdPerf($appRoot, $options),
                'prove:isolation' => $this->cmdProveIsolation($appRoot, $options),
                'snapshot' => $this->cmdSnapshot($appRoot, $options),
                'impact' => $this->cmdImpact($appRoot, $options),
                'shadow:record' => $this->cmdShadowRecord($appRoot, $options),
                'shadow:diff' => $this->cmdShadowDiff($appRoot, $options),
                'upgrade' => $this->cmdUpgrade($appRoot, $options),
                'watch' => $this->cmdWatch($appRoot, $options),
                'incident:list' => $this->cmdIncidentList($appRoot, $options),
                'incident:show' => $this->cmdIncidentShow($appRoot, $rest, $options),
                'incident:replay' => $this->cmdIncidentReplay($appRoot, $rest, $options),
                'incident:test' => $this->cmdIncidentTest($appRoot, $rest, $options),
                'eval' => $this->cmdEval($appRoot, $options),
                'deploy:init' => $this->cmdDeployInit($appRoot, $options),
                'serve' => $this->cmdServe($appRoot, $options),
                'tinker' => $this->cmdTinker($appRoot, $options),
                'new' => $this->cmdNew($rest, $options),
                'work' => $this->cmdWork($appRoot, $options),
                'queue:failed' => $this->cmdQueueFailed($appRoot, $options),
                'queue:retry' => $this->cmdQueueRetry($appRoot, $rest, $options),
                'queue:flush' => $this->cmdQueueFlush($appRoot, $options),
                'schedule:run' => $this->cmdScheduleRun($appRoot),
                'migrate' => $this->cmdMigrate($appRoot, $options),
                'migrate:fresh' => $this->cmdMigrateFresh($appRoot, $options),
                'migrate:rollback' => $this->cmdMigrateRollback($appRoot, $options),
                'migrate:status' => $this->cmdMigrateStatus($appRoot),
                'migrate:lint' => $this->cmdMigrateLint($appRoot, $options),
                'db:seed' => $this->cmdDbSeed($appRoot),
                'optimize' => $this->cmdOptimize($appRoot),
                'cache:clear' => $this->cmdCacheClear($appRoot),
                'make:controller' => $this->cmdMake('controller', $appRoot, $rest, $options),
                'make:dto' => $this->cmdMake('dto', $appRoot, $rest, $options),
                'make:migration' => $this->cmdMake('migration', $appRoot, $rest, $options),
                'make:seeder' => $this->cmdMake('seeder', $appRoot, $rest, $options),
                'make:test' => $this->cmdMake('test', $appRoot, $rest, $options),
                'make:job' => $this->cmdMake('job', $appRoot, $rest, $options),
                'make:policy' => $this->cmdMake('policy', $appRoot, $rest, $options),
                'make:middleware' => $this->cmdMake('middleware', $appRoot, $rest, $options),
                'make:factory' => $this->cmdMake('factory', $appRoot, $rest, $options),
                'make:rule' => $this->cmdMake('rule', $appRoot, $rest, $options),
                'make:mail' => $this->cmdMake('mail', $appRoot, $rest, $options),
                'make:resource' => $this->cmdMake('resource', $appRoot, $rest, $options),
                'key:generate' => [0, 'APP_KEY=' . Encryptor::generateKey() . "\n"],
                'doctor' => $this->cmdDoctor($appRoot),
                'env:check' => $this->cmdEnvCheck($appRoot),
                'check' => $this->cmdCheck($appRoot),
                'test' => $this->cmdHantei($appRoot, ['test'], $options),
                'oshie' => $this->cmdOshie($appRoot, $rest, $options),
                'chie' => $this->cmdChie($appRoot, $rest, $options),
                'hantei' => $this->cmdHantei($appRoot, $rest, $options),
                default => $this->unknownCommand($command),
            };
        } catch (Throwable $e) {
            $exp = (new ErrorExplainer())->explainThrowable(
                exception: $e,
                basePath: $appRoot,
            );
            if (isset($options['json'])) {
                return [1, json_encode(['error' => $exp->toArray()], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
            }

            return [1, $this->formatCliExplanation($exp->toArray())];
        }
    }

    /** @return array{0: int, 1: string} */
    private function unknownCommand(string $command): array
    {
        $known = [
            'help', 'version', 'routes', 'graph', 'openapi', 'sdk:generate', 'bridge:init',
            'bridge:types', 'ui:add', 'auth:init', 'skills:install', 'mcp', 'lsp', 'audit', 'diagnose', 'prove',
            'policy:check', 'perf', 'prove:isolation', 'snapshot', 'impact', 'shadow:record', 'shadow:diff', 'upgrade', 'watch',
            'incident:list', 'incident:show', 'incident:replay', 'incident:test',
            'eval', 'deploy:init', 'serve', 'tinker', 'new', 'work', 'queue:failed', 'queue:retry',
            'queue:flush', 'schedule:run', 'migrate', 'migrate:fresh', 'migrate:rollback',
            'migrate:status', 'migrate:lint', 'db:seed', 'optimize', 'cache:clear',
            'make:controller', 'make:dto', 'make:migration', 'make:seeder', 'make:test',
            'make:job', 'make:policy', 'make:middleware', 'make:factory', 'make:rule',
            'make:mail', 'make:resource', 'key:generate', 'doctor', 'env:check', 'check',
            'test', 'oshie', 'chie', 'hantei',
        ];
        $best = null;
        $bestDist = 99;
        $needle = strtolower($command);
        foreach ($known as $cand) {
            $d = levenshtein($needle, $cand);
            if ($d < $bestDist && $d <= max(2, (int) floor(strlen($needle) / 2))) {
                $bestDist = $d;
                $best = $cand;
            }
        }
        $hint = null !== $best
            ? sprintf("  Did you mean: `kongo %s`?\n", $best)
            : '';

        return [1, sprintf("Unknown command \"%s\".\n%sRun `kongo help` for usage.\n", $command, $hint)];
    }

    /**
     * @param array{
     *     code: string,
     *     who: string,
     *     what: string,
     *     why: string,
     *     where: string,
     *     when: string,
     *     how_to_fix: list<string>,
     *     code_fix: ?string,
     *     command: ?string,
     *     did_you_mean: list<string>
     * } $exp
     */
    private function formatCliExplanation(array $exp): string
    {
        $lines = [
            sprintf('Error [%s]: %s', $exp['code'], $exp['what']),
            sprintf('  Why:   %s', $exp['why']),
            sprintf('  Who:   %s', $exp['who']),
            sprintf('  Where: %s', $exp['where']),
            sprintf('  When:  %s', $exp['when']),
        ];
        if ([] !== $exp['did_you_mean']) {
            $lines[] = sprintf('  Did you mean: %s', implode(', ', $exp['did_you_mean']));
        }
        if ([] !== $exp['how_to_fix']) {
            $lines[] = '  How to fix:';
            foreach ($exp['how_to_fix'] as $idx => $step) {
                $lines[] = sprintf('    %d. %s', $idx + 1, $step);
            }
        }
        if (null !== $exp['command'] && '' !== $exp['command']) {
            $lines[] = sprintf('  Run:   %s', $exp['command']);
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdRoutes(string $appRoot, array $options = []): array
    {
        $context = ProjectContext::discover($appRoot);
        if (null === $context->graph) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        if (isset($options['json'])) {
            $payload = array_map(static fn(RouteNode $r): array => $r->toArray(), $context->graph->routes);

            return [0, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [
            sprintf('Routes for %s (%d total):', $context->graph->appName, count($context->graph->routes)),
            str_repeat('-', 78),
            sprintf('%-14s %-28s %-20s %s', 'METHODS', 'PATH', 'NAME', 'HANDLER'),
            str_repeat('-', 78),
        ];

        foreach ($context->graph->routes as $route) {
            $lines[] = sprintf(
                '%-14s %-28s %-20s %s',
                implode('|', $route->methods),
                $route->path,
                $route->name ?? '-',
                $route->handler,
            );
        }

        return [0, implode("\n", $lines) . "\n"];
    }

    /** @return array{0: int, 1: string} */
    private function cmdGraph(string $appRoot): array
    {
        $context = ProjectContext::discover($appRoot);
        if (null === $context->graph) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        return [0, $context->graph->toJson(pretty: true) . "\n"];
    }

    /** @return array{0: int, 1: string} */
    private function cmdOpenApi(string $appRoot): array
    {
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }
        $app = require $bootstrap;
        if (!$app instanceof App) {
            return [1, "app/bootstrap.php did not return a Kongono\\Http\\App instance.\n"];
        }

        return [0, (new OpenApiGenerator())->generateJson($app) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdSdkGenerate(string $appRoot, array $options): array
    {
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }
        $app = require $bootstrap;
        if (!$app instanceof App) {
            return [1, "app/bootstrap.php did not return a Kongono\\Http\\App instance.\n"];
        }

        $lang = is_string($options['lang'] ?? null) && '' !== trim($options['lang']) ? trim($options['lang']) : 'ts';
        $clientName = is_string($options['name'] ?? null) && '' !== trim($options['name']) ? trim($options['name']) : 'KongonoClient';

        $generator = new SdkGenerator();
        $code = $generator->generate($app, $lang, $clientName);

        if (is_string($options['out'] ?? null) && '' !== trim($options['out'])) {
            $outPath = trim($options['out']);
            $dest = $this->writeSafeCliFile($appRoot, $outPath, $code, allowAbsolute: true);

            return [0, sprintf("Generated %s SDK (%d bytes) -> %s\n", strtoupper($generator->normalizeLanguage($lang)), strlen($code), $dest)];
        }

        return [0, $code];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdMcp(string $appRoot, array $options = []): array
    {
        $allowBootstrap = isset($options['no-bootstrap']) || isset($options['safe-mode']) ? false : null;
        $server = KongonoMcpFactory::forApp($appRoot, allowBootstrap: $allowBootstrap);
        $server->serveStdio();

        return [0, ''];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdLsp(string $appRoot, array $options = []): array
    {
        $allowBootstrap = isset($options['no-bootstrap']) || isset($options['safe-mode']) ? false : null;
        $server = new KongonoLanguageServer($appRoot, allowBootstrap: $allowBootstrap);
        $code = $server->serveStreams(STDIN, STDOUT);

        return [$code, ''];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdServe(string $appRoot, array $options): array
    {
        $rawHost = is_string($options['host'] ?? null) ? trim($options['host']) : '127.0.0.1';
        $rawPort = is_string($options['port'] ?? null) ? trim($options['port']) : '8000';
        if ('' === $rawPort || !ctype_digit($rawPort) || (int) $rawPort < 1 || (int) $rawPort > 65535) {
            return [1, sprintf("Invalid --port \"%s\" (expected 1..65535).\n", $rawPort)];
        }
        $port = (string) (int) $rawPort;
        $unbracketed = str_starts_with($rawHost, '[') && str_ends_with($rawHost, ']')
            ? substr($rawHost, 1, -1)
            : $rawHost;
        if (false !== filter_var($unbracketed, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $host = '[' . $unbracketed . ']';
        } elseif (
            false !== filter_var($rawHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            || 1 === preg_match('/\A[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\z/', $rawHost)
        ) {
            $host = $rawHost;
        } else {
            return [1, sprintf("Invalid --host \"%s\".\n", $rawHost)];
        }
        $publicDir = $appRoot . DIRECTORY_SEPARATOR . 'public';
        $routerScript = $publicDir . DIRECTORY_SEPARATOR . 'index.php';

        if (!is_dir($publicDir) || !is_file($routerScript)) {
            return [1, sprintf("Cannot serve: %s does not exist.\n", $routerScript)];
        }

        if (!isset($options['no-diagnose'])) {
            $banner = $this->formatStartupDiagnostics($appRoot);
            if ('' !== $banner) {
                fwrite(STDOUT, $banner);
            }
        }

        $address = sprintf('%s:%s', $host, $port);
        fwrite(STDOUT, sprintf("Kongono dev server running at http://%s (app: %s)\nPress Ctrl+C to stop.\n", $address, $appRoot));

        $cmd = [PHP_BINARY, '-S', $address, '-t', $publicDir, $routerScript];
        $process = proc_open($cmd, [STDIN, STDOUT, STDERR], $pipes, $appRoot);
        if (!is_resource($process)) {
            return [1, "Failed to launch PHP built-in server.\n"];
        }
        $code = proc_close($process);

        return [$code, ''];
    }

    /**
     * Fast non-blocking diagnostic summary printed on `kongo serve` boot in local development.
     */
    public function formatStartupDiagnostics(string $appRoot): string
    {
        $app = null;
        $bootError = null;
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (is_file($bootstrap)) {
            try {
                $loaded = require $bootstrap;
                if ($loaded instanceof App) {
                    $app = $loaded;
                }
            } catch (Throwable $e) {
                $bootError = (new ErrorExplainer())->explainThrowable($e, basePath: $appRoot)->toArray();
            }
        }

        if (null !== $bootError) {
            return "[diagnose] Boot error detected:\n" . $this->formatCliExplanation($bootError);
        }

        $report = (new KongonoAppInspector())->inspect($appRoot, $app);
        if ($report['passed'] && 0 === $report['counts']['warning']) {
            return sprintf(
                "[diagnose] %d/100 PASS (%d route(s), %d view(s), %d PHP file(s))\n",
                $report['score'],
                $report['routes_checked'],
                $report['views_checked'],
                $report['php_files_checked'],
            );
        }

        $lines = [
            sprintf(
                '[diagnose] Score: %d/100 (%d error(s), %d warning(s))',
                $report['score'],
                $report['counts']['error'],
                $report['counts']['warning'],
            ),
        ];
        foreach (array_slice($report['findings'], 0, 5) as $f) {
            $lines[] = sprintf('  [%-7s] %s (%s): %s', strtoupper($f['severity']), $f['code'], $f['where'], $f['what']);
        }
        if (count($report['findings']) > 5) {
            $lines[] = sprintf('  ... and %d more finding(s). Run `kongo diagnose` for full 5W1H fixes.', count($report['findings']) - 5);
        } elseif ($report['fixable_count'] > 0) {
            $lines[] = sprintf('  Tip: %d issue(s) can be auto-fixed with `kongo diagnose --fix`.', $report['fixable_count']);
        } else {
            $lines[] = '  Run `kongo diagnose` for full 5W1H explanations and fixes.';
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param list<string>               $args
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdNew(array $args, array $options): array
    {
        $dir = $args[0] ?? '';
        if ('' === $dir) {
            return [1, "Usage: kongo new <directory> [--name=\"My App\"] [--starter=minimal|api|saas]\n"];
        }
        $target = str_starts_with($dir, '/') || (strlen($dir) > 1 && ':' === $dir[1])
            ? $dir
            : $this->workingDirectory . DIRECTORY_SEPARATOR . $dir;
        $name = is_string($options['name'] ?? null) ? $options['name'] : ucwords(str_replace(['-', '_'], ' ', basename($dir)));
        $starter = is_string($options['starter'] ?? null) ? $options['starter'] : 'minimal';

        $created = (new AppScaffolder())->scaffold($target, $name, $starter);
        $lines = [
            sprintf('Created new Kongono v1 application "%s" (starter: %s) in %s:', $name, $starter, $target),
            ...array_map(static fn(string $f): string => '  + ' . $f, $created),
            '',
            'Next steps:',
            sprintf('  php bin/kongo serve --app=%s', $dir),
        ];

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdWork(string $appRoot, array $options): array
    {
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $app = require $bootstrap;
        if (!$app instanceof App || !$app->container()->has(Worker::class)) {
            return [1, "No Kongono\\Jobs\\Worker registered in this application's container.\n"];
        }

        $worker = $app->container()->make(Worker::class);
        $queue = is_string($options['queue'] ?? null) ? $options['queue'] : 'default';
        $maxJobs = is_string($options['max-jobs'] ?? null) && is_numeric($options['max-jobs']) ? max(1, (int) $options['max-jobs']) : 50;

        if (isset($options['daemon'])) {
            $minSleep = is_string($options['sleep'] ?? null) && is_numeric($options['sleep']) ? max(1, (int) $options['sleep']) : 50;
            $maxSleep = is_string($options['max-sleep'] ?? null) && is_numeric($options['max-sleep']) ? max($minSleep, (int) $options['max-sleep']) : 1000;
            $maxRuntime = is_string($options['max-runtime'] ?? null) && is_numeric($options['max-runtime']) ? max(1, (int) $options['max-runtime']) : null;
            $pruneAfter = is_string($options['prune-after'] ?? null) && is_numeric($options['prune-after']) ? max(0, (int) $options['prune-after']) : 86_400;
            $report = $worker->runDaemon(
                queue: $queue,
                batchSize: $maxJobs,
                minSleepMs: $minSleep,
                maxSleepMs: $maxSleep,
                pruneAfterSeconds: $pruneAfter,
                maxRuntimeSeconds: $maxRuntime,
            );
        } else {
            $report = $worker->run($queue, $maxJobs);
        }

        return [0, sprintf(
            "Worker finished queue \"%s\": processed=%d completed=%d retried=%d failed=%d uncertain=%d stale=%d\n",
            implode(',', Worker::normalizeQueues($queue)),
            $report->processed,
            $report->completed,
            $report->retried,
            $report->failed,
            $report->uncertain,
            $report->stale,
        )];
    }

    /**
     * List failed and dead-lettered jobs in a queue (`kongo queue:failed [--queue=default] [--limit=50] [--json]`).
     *
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdQueueFailed(string $appRoot, array $options): array
    {
        $driver = $this->resolveQueueDriver($appRoot, $errMsg);
        if (null === $driver) {
            return [1, $errMsg ?? "Unable to resolve queue driver.\n"];
        }

        $queue = is_string($options['queue'] ?? null) && '' !== trim($options['queue']) ? trim($options['queue']) : 'default';
        $limit = is_string($options['limit'] ?? null) && is_numeric($options['limit']) ? max(1, (int) $options['limit']) : 50;
        $jobs = $driver->listFailed($queue, $limit);

        if (isset($options['json'])) {
            $payload = array_map(static fn(\Kongono\Jobs\Job $j): array => [
                'id' => $j->id,
                'queue' => $j->queue,
                'type' => $j->type,
                'status' => $j->status->value,
                'attempt' => $j->attempt,
                'max_attempts' => $j->maxAttempts,
                'last_error' => null !== $j->lastError ? SecretScrubber::scrub($j->lastError) : null,
            ], $jobs);

            return [0, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        if ([] === $jobs) {
            return [0, sprintf("No failed or dead-lettered jobs in queue \"%s\".\n", $queue)];
        }

        $lines = [
            sprintf('Failed / dead jobs in queue "%s" (%d shown):', $queue, count($jobs)),
            str_repeat('-', 78),
        ];
        foreach ($jobs as $job) {
            $lines[] = sprintf(
                '  [%-6s] %s  type=%s  attempts=%d/%d  error=%s',
                strtoupper($job->status->value),
                $job->id,
                $job->type,
                $job->attempt,
                $job->maxAttempts,
                null !== $job->lastError ? SecretScrubber::scrub($job->lastError) : '-',
            );
        }

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * Replay one failed/dead job by ID or all failed jobs (`kongo queue:retry <id>|--all [--queue=default]`).
     *
     * @param list<string>               $args
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdQueueRetry(string $appRoot, array $args, array $options): array
    {
        $driver = $this->resolveQueueDriver($appRoot, $errMsg);
        if (null === $driver) {
            return [1, $errMsg ?? "Unable to resolve queue driver.\n"];
        }

        $queue = is_string($options['queue'] ?? null) && '' !== trim($options['queue']) ? trim($options['queue']) : 'default';
        $retryAll = isset($options['all']) || 'all' === ($args[0] ?? '');
        $jobId = !$retryAll ? ($args[0] ?? '') : null;

        if (!$retryAll && '' === $jobId) {
            return [1, "Usage: kongo queue:retry <job-id> | --all [--queue=default] [--app=path]\n"];
        }

        $count = $driver->retryFailed($queue, $jobId);

        return [0, sprintf("Requeued %d failed job(s) in queue \"%s\".\n", $count, $queue)];
    }

    /**
     * Permanently flush failed and dead-lettered jobs (`kongo queue:flush [--queue=default]`).
     *
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdQueueFlush(string $appRoot, array $options): array
    {
        $driver = $this->resolveQueueDriver($appRoot, $errMsg);
        if (null === $driver) {
            return [1, $errMsg ?? "Unable to resolve queue driver.\n"];
        }

        $queue = is_string($options['queue'] ?? null) && '' !== trim($options['queue']) ? trim($options['queue']) : 'default';
        $removed = $driver->flushFailed($queue);

        return [0, sprintf("Flushed %d failed job(s) from queue \"%s\".\n", $removed, $queue)];
    }

    private function resolveQueueDriver(string $appRoot, ?string &$error = null): ?QueueDriverInterface
    {
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            $error = sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot);

            return null;
        }

        $app = require $bootstrap;
        if (!$app instanceof App) {
            $error = "app/bootstrap.php did not return a Kongono\\Http\\App instance.\n";

            return null;
        }

        $container = $app->container();
        if ($container->has(QueueDriverInterface::class)) {
            return $container->make(QueueDriverInterface::class);
        }
        if ($container->has(Queue::class)) {
            return $container->make(Queue::class)->driver();
        }
        if ($container->has(Worker::class)) {
            return $container->make(Worker::class)->driver();
        }

        $error = "No Kongono\\Jobs\\QueueDriverInterface, Queue, or Worker registered in this application's container.\n";

        return null;
    }

    /** @return array{0: int, 1: string} */
    private function cmdScheduleRun(string $appRoot): array
    {
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $app = require $bootstrap;
        if (!$app instanceof App || !$app->container()->has(Scheduler::class)) {
            return [1, "No Kongono\\Jobs\\Scheduler\\Scheduler registered in this application's container.\n"];
        }

        $scheduler = $app->container()->make(Scheduler::class);
        $lockStore = $app->container()->has(AtomicCacheInterface::class)
            ? $app->container()->make(AtomicCacheInterface::class)
            : null;
        $report = $scheduler->runDue(lockStore: $lockStore);
        $lines = [
            sprintf(
                'Scheduler evaluated %d task(s): ran=%d skipped=%d failed=%d',
                count($scheduler->tasks()),
                count($report['ran']),
                count($report['skipped']),
                count($report['failed']),
            ),
        ];
        foreach ($report['ran'] as $name) {
            $lines[] = '  [OK] ' . $name;
        }
        foreach ($report['skipped'] as $name) {
            $lines[] = '  [SKIP] ' . $name . ' (locked)';
        }
        foreach ($report['failed'] as $name => $err) {
            $lines[] = sprintf('  [FAIL] %s: %s', $name, $err);
        }

        return [[] === $report['failed'] ? 0 : 1, implode("\n", $lines) . "\n"];
    }

    /** @return array{0: int, 1: string} */
    private function cmdCheck(string $appRoot): array
    {
        $context = ProjectContext::discover($appRoot);
        $hantei = new HanteiDebugger($this->tryAiClient());
        $rootContext = new ProjectContext($this->workingDirectory, $context->graph);

        $gates = [
            'cs' => [PHP_BINARY, $this->workingDirectory . '/vendor/bin/php-cs-fixer', 'fix', '--config=.php-cs-fixer.dist.php', '--dry-run', '--diff'],
            'analyse' => [PHP_BINARY, '-d', 'memory_limit=512M', $this->workingDirectory . '/vendor/bin/phpstan', 'analyse', '--configuration=phpstan.neon.dist', '--no-progress'],
            'test' => [PHP_BINARY, '-d', 'memory_limit=512M', $this->workingDirectory . '/vendor/bin/phpunit', '--configuration', 'phpunit.xml.dist'],
        ];

        $lines = ['Kongono Quality Suite:'];
        $failed = false;

        foreach ($gates as $name => $cmd) {
            $res = $hantei->runCheck($cmd, $rootContext);
            $status = 0 === $res['exit_code'] ? 'PASS' : 'FAIL';
            $lines[] = sprintf('  [%-4s] %s', $status, $name);
            if (0 !== $res['exit_code']) {
                $failed = true;
                $lines[] = $res['output'];
                if (null !== $res['diagnosis']) {
                    $lines[] = "--- Hantei AI Diagnosis ---\n" . $res['diagnosis'];
                }
                break;
            }
        }

        if (!$failed && (is_file($appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php') || is_dir($appRoot . DIRECTORY_SEPARATOR . 'views'))) {
            [$diagCode, $diagOut] = $this->cmdDiagnose($appRoot, []);
            $lines[] = sprintf('  [%-4s] diagnose', 0 === $diagCode ? 'PASS' : 'FAIL');
            if (0 !== $diagCode) {
                $failed = true;
                $lines[] = rtrim($diagOut);
            } else {
                [$policyCode, $policyOut] = $this->cmdPolicyCheck($appRoot, []);
                $lines[] = sprintf('  [%-4s] policy:check', 0 === $policyCode ? 'PASS' : 'FAIL');
                if (0 !== $policyCode) {
                    $failed = true;
                    $lines[] = rtrim($policyOut);
                }
            }
        }

        return [$failed ? 1 : 0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param 'controller'|'dto'|'migration'|'seeder'|'test'|'job'|'policy'|'middleware'|'factory'|'rule'|'mail'|'resource' $kind
     * @param list<string>                                                                                                  $args
     * @param array<string, string|true>                                                                                    $options
     * @return array{0: int, 1: string}
     */
    private function cmdMake(string $kind, string $appRoot, array $args, array $options = []): array
    {
        $name = $args[0] ?? '';
        if ('' === $name) {
            return [1, sprintf("Usage: kongo make:%s <Name> [--app=path]\n", $kind)];
        }

        $context = ProjectContext::discover($appRoot);
        $generator = new CodeGenerator();
        $alterTable = is_string($options['table'] ?? null) ? $options['table'] : null;
        $created = match ($kind) {
            'controller' => $generator->makeController($context, $name),
            'dto' => $generator->makeDto($context, $name),
            'migration' => $generator->makeMigration($context, $name, $alterTable),
            'seeder' => $generator->makeSeeder($context, $name),
            'test' => $generator->makeTest($context, $name),
            'job' => $generator->makeJob($context, $name),
            'policy' => $generator->makePolicy($context, $name),
            'middleware' => $generator->makeMiddleware($context, $name),
            'factory' => $generator->makeFactory($context, $name),
            'rule' => $generator->makeRule($context, $name),
            'mail' => $generator->makeMail($context, $name),
            'resource' => $generator->makeResource($context, $name),
        };

        if (is_array($created)) {
            $lines = [sprintf('Created %s (%d files):', $kind, count($created))];
            foreach ($created as $path) {
                $lines[] = '  + ' . $path;
            }

            return [0, implode("\n", $lines) . "\n"];
        }

        return [0, sprintf("Created %s: %s\n", $kind, $created)];
    }

    /** @return array{0: int, 1: string} */
    private function cmdOptimize(string $appRoot): array
    {
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $app = require $bootstrap;
        if (!$app instanceof App) {
            return [1, "app/bootstrap.php did not return a Kongono\\Http\\App instance.\n"];
        }

        $basePath = $app->basePath();
        $varDir = $basePath . DIRECTORY_SEPARATOR . 'var';
        $cacheDir = $varDir . DIRECTORY_SEPARATOR . 'cache';
        if (is_link($varDir) || is_link($cacheDir)) {
            throw new CoreException('Refusing to optimize: var/cache cannot be a symbolic link.');
        }
        if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0o775, true) && !is_dir($cacheDir)) {
            return [1, sprintf("Unable to create cache directory %s.\n", $cacheDir)];
        }

        $compiledViews = 0;
        if ($app->container()->has(ViewEngine::class)) {
            $compiledViews = $app->container()->make(ViewEngine::class)->warmCache();
        }

        $this->writeSafeCliFile($basePath, 'var/cache/graph.json', $app->graph()->toJson(pretty: true));
        $this->writeSafeCliFile($basePath, 'var/cache/openapi.json', (new OpenApiGenerator())->generateJson($app));
        $this->writeSafeCliFile($basePath, 'var/cache/routes.compiled.php', $app->router()->exportCompiledPhp());

        return [0, sprintf(
            "Optimized \"%s\":\n  + Precompiled %d template(s)\n  + Compiled var/cache/routes.compiled.php\n  + Cached var/cache/graph.json\n  + Cached var/cache/openapi.json\n",
            $app->graph()->appName,
            $compiledViews,
        )];
    }

    /** @return array{0: int, 1: string} */
    private function cmdCacheClear(string $appRoot): array
    {
        $realRoot = realpath($appRoot);
        $varDir = $appRoot . DIRECTORY_SEPARATOR . 'var';
        $cacheDir = $varDir . DIRECTORY_SEPARATOR . 'cache';
        if (is_link($varDir) || is_link($cacheDir)) {
            throw new CoreException('Refusing to clear cache directory: var or var/cache cannot be a symbolic link.');
        }
        $realCache = realpath($cacheDir);
        if (false === $realCache || !is_dir($realCache)) {
            return [0, "Cache directory is already empty.\n"];
        }
        if (false !== $realRoot) {
            $prefix = rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (!str_starts_with($realCache, $prefix)) {
                throw new CoreException('Refusing to clear cache directory: resolved path escapes project root.');
            }
        }

        $removed = $this->deleteDirectoryFiles($realCache, $realCache);

        return [0, sprintf("Cleared %d cached file(s) under %s.\n", $removed, $cacheDir)];
    }

    private function deleteDirectoryFiles(string $dir, string $rootBoundary): int
    {
        $count = 0;
        $entries = scandir($dir);
        if (false === $entries) {
            return 0;
        }
        $prefix = rtrim($rootBoundary, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            // Never traverse symbolic links when deleting directories (prevents arbitrary external deletion)
            if (is_link($path)) {
                if (@unlink($path)) {
                    ++$count;
                }
                continue;
            }
            if (is_dir($path)) {
                $realChild = realpath($path);
                if (false === $realChild || !str_starts_with($realChild, $prefix)) {
                    continue;
                }
                $count += $this->deleteDirectoryFiles($realChild, $rootBoundary);
                @rmdir($realChild);
            } elseif (@unlink($path)) {
                ++$count;
            }
        }

        return $count;
    }

    /** @return array{0: int, 1: string} */
    private function cmdDbSeed(string $appRoot): array
    {
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $app = require $bootstrap;
        if (!$app instanceof App || !$app->container()->has(ConnectionInterface::class)) {
            return [1, "No Kongono\\Database\\ConnectionInterface registered in this application's container.\n"];
        }

        $db = $app->container()->make(ConnectionInterface::class);
        $ran = $this->runSeeders($appRoot, $db);
        if (null === $ran) {
            return [0, "No seeder files found.\n"];
        }
        if ([] === $ran) {
            return [0, "No SeederInterface classes executed.\n"];
        }

        return [0, "Executed seeders:\n" . implode("\n", array_map(static fn(string $f): string => '  + ' . $f, $ran)) . "\n"];
    }

    /**
     * Execute all SeederInterface files under `seeders/` against the given connection.
     *
     * @return list<string>|null Null when no seeder files exist
     */
    private function runSeeders(string $appRoot, ConnectionInterface $db): ?array
    {
        $seedersDir = $appRoot . DIRECTORY_SEPARATOR . 'seeders';
        $files = is_dir($seedersDir) ? glob($seedersDir . DIRECTORY_SEPARATOR . '*.php') : [];
        if (false === $files || [] === $files) {
            return null;
        }
        sort($files, SORT_STRING);

        $ran = [];
        foreach ($files as $file) {
            $loaded = require $file;
            if ($loaded instanceof SeederInterface) {
                $loaded->run($db);
                $ran[] = basename($file);
            }
        }

        return $ran;
    }

    /** @return array{0: int, 1: string} */
    private function cmdDoctor(string $appRoot): array
    {
        $lines = ['Kongono Environment & Application Doctor:'];
        $allOk = true;

        $phpOk = !SecurityAuditor::isVulnerablePhpVersion(PHP_VERSION);
        $lines[] = sprintf(
            '  [%s] PHP version: %s (requires >= %s for 2026 PHP CVE patches)',
            $phpOk ? 'OK  ' : 'FAIL',
            PHP_VERSION,
            SecurityAuditor::MIN_SAFE_PHP_85,
        );
        if (!$phpOk) {
            $allOk = false;
        }

        $uploadProgressOff = !filter_var((string) ini_get('session.upload_progress.enabled'), FILTER_VALIDATE_BOOL);
        $lines[] = sprintf('  [%s] php.ini session.upload_progress.enabled=Off', $uploadProgressOff ? 'OK  ' : 'WARN');
        $ignoreArgsOn = filter_var((string) ini_get('zend.exception_ignore_args'), FILTER_VALIDATE_BOOL);
        $lines[] = sprintf('  [%s] php.ini zend.exception_ignore_args=On', $ignoreArgsOn ? 'OK  ' : 'WARN');

        foreach (['curl', 'dom', 'json', 'mbstring', 'pdo', 'sodium'] as $ext) {
            $loaded = extension_loaded($ext);
            $lines[] = sprintf('  [%s] ext-%s', $loaded ? 'OK  ' : 'FAIL', $ext);
            if (!$loaded) {
                $allOk = false;
            }
        }

        $ai = $this->tryAiClient();
        $lines[] = null !== $ai
            ? sprintf('  [OK  ] AI companion provider: %s (%s)', $ai->providerName(), $ai->defaultModel())
            : '  [INFO] AI companion provider: offline mode (set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST)';

        $context = ProjectContext::discover($appRoot);
        if (null !== $context->graph) {
            $lines[] = sprintf(
                '  [OK  ] Application "%s": %d route(s), %d service(s), %d view(s)',
                $context->graph->appName,
                count($context->graph->routes),
                count($context->graph->services),
                count($context->graph->views),
            );
        } else {
            $lines[] = sprintf('  [WARN] No runnable app/bootstrap.php discovered in %s', $appRoot);
        }

        return [$allOk ? 0 : 1, implode("\n", $lines) . "\n"];
    }

    /** @return array{0: int, 1: string} */
    private function cmdEnvCheck(string $appRoot): array
    {
        $exampleFile = $appRoot . DIRECTORY_SEPARATOR . '.env.example';
        if (!is_file($exampleFile)) {
            return [0, sprintf("No .env.example found in %s; nothing to compare.\n", $appRoot)];
        }

        $exampleLines = file($exampleFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (false === $exampleLines) {
            return [1, "Unable to read .env.example.\n"];
        }

        $requiredKeys = [];
        foreach ($exampleLines as $line) {
            $trimmed = trim($line);
            if ('' === $trimmed || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                continue;
            }
            [$key] = explode('=', $trimmed, 2);
            $key = trim($key);
            if ('' !== $key) {
                $requiredKeys[] = $key;
            }
        }

        $envFile = $appRoot . DIRECTORY_SEPARATOR . '.env';
        $present = [];
        if (is_file($envFile)) {
            $rawEnvLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $envLines = false !== $rawEnvLines ? $rawEnvLines : [];
            foreach ($envLines as $line) {
                $trimmed = trim($line);
                if ('' === $trimmed || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
                    continue;
                }
                [$k] = explode('=', $trimmed, 2);
                $present[trim($k)] = true;
            }
        }

        $missing = [];
        $lines = [sprintf('Environment check (%s):', $appRoot)];
        foreach ($requiredKeys as $key) {
            $inEnv = isset($present[$key]) || false !== getenv($key);
            $lines[] = sprintf('  [%s] %s', $inEnv ? 'OK  ' : 'MISS', $key);
            if (!$inEnv) {
                $missing[] = $key;
            }
        }

        return [[] === $missing ? 0 : 1, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdMigrateRollback(string $appRoot, array $options): array
    {
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $app = require $bootstrap;
        if (!$app instanceof App || !$app->container()->has(ConnectionInterface::class)) {
            return [1, "No Kongono\\Database\\ConnectionInterface registered in this application's container.\n"];
        }

        $migrationsDir = $appRoot . DIRECTORY_SEPARATOR . 'migrations';
        $files = is_dir($migrationsDir) ? glob($migrationsDir . DIRECTORY_SEPARATOR . '*.php') : [];
        if (false === $files || [] === $files) {
            return [0, "No migration files found.\n"];
        }
        sort($files, SORT_STRING);

        $migrations = [];
        foreach ($files as $file) {
            $loaded = require $file;
            if ($loaded instanceof MigrationInterface) {
                $migrations[] = $loaded;
            }
        }

        $stepsRaw = is_string($options['steps'] ?? null) && is_numeric($options['steps']) ? (int) $options['steps'] : 1;
        $steps = max(1, $stepsRaw);

        $db = $app->container()->make(ConnectionInterface::class);
        $rolledBack = (new Migrator($db))->rollback($migrations, $steps);
        if ([] === $rolledBack) {
            return [0, "No applied migrations to roll back.\n"];
        }

        return [0, "Rolled back migrations:\n" . implode("\n", array_map(static fn(string $id): string => '  - ' . $id, $rolledBack)) . "\n"];
    }

    /** @return array{0: int, 1: string} */
    private function cmdMigrateStatus(string $appRoot): array
    {
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $app = require $bootstrap;
        if (!$app instanceof App || !$app->container()->has(ConnectionInterface::class)) {
            return [1, "No Kongono\\Database\\ConnectionInterface registered in this application's container.\n"];
        }

        $migrationsDir = $appRoot . DIRECTORY_SEPARATOR . 'migrations';
        $files = is_dir($migrationsDir) ? glob($migrationsDir . DIRECTORY_SEPARATOR . '*.php') : [];
        if (false === $files || [] === $files) {
            return [0, "No migration files found.\n"];
        }
        sort($files, SORT_STRING);

        $migrations = [];
        foreach ($files as $file) {
            $loaded = require $file;
            if ($loaded instanceof MigrationInterface) {
                $migrations[] = $loaded;
            }
        }

        $db = $app->container()->make(ConnectionInterface::class);
        $rows = (new Migrator($db))->status($migrations);
        $lines = [
            sprintf('Migration status (%d total):', count($rows)),
            str_repeat('-', 56),
        ];
        foreach ($rows as $row) {
            $lines[] = sprintf('  [%-7s] %s', $row['applied'] ? 'APPLIED' : 'PENDING', $row['id']);
        }

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * Interactive PHP 8.5 REPL (or single-shot `--eval="..."`) pre-booted with `$app`, `$container`, `$router`, and `$graph`.
     *
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdTinker(string $appRoot, array $options): array
    {
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $app = require $bootstrap;
        if (!$app instanceof App) {
            return [1, "app/bootstrap.php did not return a Kongono\\Http\\App instance.\n"];
        }

        $container = $app->container();
        $router = $app->router();
        $graph = $app->graph();

        $evalCode = is_string($options['eval'] ?? null) ? trim($options['eval']) : null;
        if (null !== $evalCode && '' !== $evalCode) {
            $result = $this->evalTinkerSnippet($evalCode, $app, $container, $router, $graph);

            return [$result['ok'] ? 0 : 1, $result['output'] . "\n"];
        }

        fwrite(STDOUT, sprintf("Kongono Tinker — %s (PHP %s)\n", $graph->appName, PHP_VERSION));
        fwrite(STDOUT, "Available: \$app, \$container, \$router, \$graph  |  Commands: :routes, :services, :graph, :exit\n");

        while (true) {
            fwrite(STDOUT, "\ntinker> ");
            $line = fgets(STDIN);
            if (false === $line) {
                break;
            }
            $input = trim($line);
            if ('' === $input) {
                continue;
            }
            if (':exit' === $input || ':quit' === $input || 'exit' === $input) {
                break;
            }
            if (':routes' === $input) {
                foreach ($graph->routes as $r) {
                    fwrite(STDOUT, sprintf("  %-10s %s -> %s\n", implode('|', $r->methods), $r->path, $r->handler));
                }
                continue;
            }
            if (':services' === $input) {
                foreach ($graph->services as $s) {
                    fwrite(STDOUT, sprintf("  [%-9s] %s\n", $s->lifetime, $s->id));
                }
                continue;
            }
            if (':graph' === $input) {
                fwrite(STDOUT, $graph->toJson(pretty: true) . "\n");
                continue;
            }

            $res = $this->evalTinkerSnippet($input, $app, $container, $router, $graph);
            fwrite(STDOUT, $res['output'] . "\n");
        }

        return [0, "Goodbye from Tinker.\n"];
    }

    /**
     * @return array{ok: bool, output: string}
     */
    private function evalTinkerSnippet(
        string $code,
        App $app,
        \Kongono\Core\Container $container,
        \Kongono\Http\Routing\Router $router,
        \Kongono\Core\Graph\ApplicationGraph $graph,
    ): array {
        $trimmed = rtrim($code, "; \t\n\r");
        $isStatement = str_starts_with($trimmed, 'echo ')
            || str_starts_with($trimmed, 'return ')
            || str_contains($trimmed, ';');
        $executable = $isStatement ? ($trimmed . ';') : ('return ' . $trimmed . ';');

        $obLevel = ob_get_level();
        ob_start();
        try {
            $value = (static function (
                string $__code,
                App $app,
                \Kongono\Core\Container $container,
                \Kongono\Http\Routing\Router $router,
                \Kongono\Core\Graph\ApplicationGraph $graph,
            ): mixed {
                return eval($__code);
            })($executable, $app, $container, $router, $graph);

            $printedParts = [];
            while (ob_get_level() > $obLevel) {
                $chunk = ob_get_clean();
                if (false !== $chunk && '' !== $chunk) {
                    array_unshift($printedParts, $chunk);
                }
            }
            $printedStr = implode('', $printedParts);
            $formatted = null === $value
                ? ($printedStr !== '' ? rtrim($printedStr, "\r\n") : 'null')
                : ($printedStr . $this->formatTinkerValue($value));

            return ['ok' => true, 'output' => $formatted];
        } catch (Throwable $e) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            return ['ok' => false, 'output' => sprintf('%s: %s', $e::class, $e->getMessage())];
        }
    }

    private function formatTinkerValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value) || is_string($value)) {
            return (string) $value;
        }
        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        }

        return print_r($value, true);
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdMigrate(string $appRoot, array $options = []): array
    {
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $app = require $bootstrap;
        if (!$app instanceof App || !$app->container()->has(ConnectionInterface::class)) {
            return [1, "No Kongono\\Database\\ConnectionInterface registered in this application's container.\n"];
        }

        $migrationsDir = $appRoot . DIRECTORY_SEPARATOR . 'migrations';
        if (!is_dir($migrationsDir)) {
            return [0, "No migrations directory found; nothing to migrate.\n"];
        }

        $migrations = $this->loadMigrations($migrationsDir);
        if (null === $migrations) {
            return [0, "No migration files found.\n"];
        }

        $db = $app->container()->make(ConnectionInterface::class);
        $migrator = new Migrator($db);

        if (isset($options['pretend'])) {
            $preview = $migrator->pretend($migrations);
            if (isset($options['json'])) {
                return [0, json_encode([
                    'pretend' => true,
                    'dialect' => $db->dialect()->value,
                    'migrations' => $preview,
                ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
            }
            if ([] === $preview) {
                return [0, "Database is already up to date (pretend mode: 0 statements).\n"];
            }

            $lines = ['Migration dry-run preview (no changes executed):'];
            $totalStatements = 0;
            foreach ($preview as $id => $statements) {
                $lines[] = '';
                $lines[] = '-- ' . $id;
                if ([] === $statements) {
                    $lines[] = '  (no SQL statements recorded)';
                } else {
                    foreach ($statements as $sql) {
                        $lines[] = '  ' . $sql . ';';
                        ++$totalStatements;
                    }
                }
            }
            $lines[] = '';
            $lines[] = sprintf(
                'Total: %d pending migration(s), %d SQL statement(s) previewed.',
                count($preview),
                $totalStatements,
            );

            return [0, implode("\n", $lines) . "\n"];
        }

        $ran = $migrator->migrate($migrations);
        if ([] === $ran) {
            return [0, "Database is already up to date.\n"];
        }

        return [0, "Applied migrations:\n" . implode("\n", array_map(static fn(string $id): string => '  + ' . $id, $ran)) . "\n"];
    }

    /**
     * Drop all user tables and re-run every migration from scratch, with a hard fail-closed
     * production environment guard unless `--force` is explicitly supplied.
     *
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdMigrateFresh(string $appRoot, array $options = []): array
    {
        $force = isset($options['force']);
        if (!$force && $this->isProductionEnvironment()) {
            return [1, "Refusing to run `kongo migrate:fresh` in production (APP_ENV=production). Pass --force to override.\n"];
        }

        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $app = require $bootstrap;
        if (!$app instanceof App || !$app->container()->has(ConnectionInterface::class)) {
            return [1, "No Kongono\\Database\\ConnectionInterface registered in this application's container.\n"];
        }

        if (!$force && $this->isProductionEnvironment($app)) {
            return [1, "Refusing to run `kongo migrate:fresh` in production (APP_ENV=production). Pass --force to override.\n"];
        }

        $migrationsDir = $appRoot . DIRECTORY_SEPARATOR . 'migrations';
        $migrations = is_dir($migrationsDir) ? ($this->loadMigrations($migrationsDir) ?? []) : [];

        $db = $app->container()->make(ConnectionInterface::class);
        $result = (new Migrator($db))->fresh($migrations);

        $lines = [
            sprintf('Dropped %d table(s).', count($result['dropped'])),
        ];
        if ([] === $result['applied']) {
            $lines[] = 'No migrations applied.';
        } else {
            $lines[] = 'Applied migrations:';
            foreach ($result['applied'] as $id) {
                $lines[] = '  + ' . $id;
            }
        }

        if (isset($options['seed'])) {
            $seeded = $this->runSeeders($appRoot, $db);
            if (null === $seeded || [] === $seeded) {
                $lines[] = 'No seeders executed.';
            } else {
                $lines[] = 'Executed seeders:';
                foreach ($seeded as $file) {
                    $lines[] = '  + ' . $file;
                }
            }
        }

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * @return list<MigrationInterface>|null Null when no migration PHP files exist
     */
    private function loadMigrations(string $migrationsDir): ?array
    {
        $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.php');
        if (false === $files || [] === $files) {
            return null;
        }
        sort($files, SORT_STRING);

        $migrations = [];
        foreach ($files as $file) {
            $loaded = require $file;
            if ($loaded instanceof MigrationInterface) {
                $migrations[] = $loaded;
            }
        }

        return $migrations;
    }

    private function isProductionEnvironment(?App $app = null): bool
    {
        $candidates = [];
        $envVal = Config::env('APP_ENV');
        if (null !== $envVal) {
            $candidates[] = strtolower(trim($envVal));
        }
        if (null !== $app && $app->container()->has(Config::class)) {
            $cfgEnv = strtolower(trim($app->container()->make(Config::class)->string('app.env', '')));
            if ('' !== $cfgEnv) {
                $candidates[] = $cfgEnv;
            }
        }

        foreach ($candidates as $val) {
            if ('production' === $val || 'prod' === $val) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string>               $args
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdOshie(string $appRoot, array $args, array $options): array
    {
        $context = ProjectContext::discover($appRoot);
        $oshie = new OshieTutor($this->tryAiClient('oshie'));
        $sessions = new CompanionSessionStore($context->rootPath);
        $maxCost = $this->parseMaxCost($options);

        if (isset($options['interactive'])) {
            return $this->runOshieRepl($oshie, $context, $maxCost);
        }

        if (isset($options['exercises'])) {
            $progress = $sessions->oshieProgress();
            $doneMap = array_flip($progress['completed_exercises']);
            $lines = ['Kongono Oshie — Verifiable Hands-On Exercises:'];
            foreach ($oshie->listExercises() as $id => $meta) {
                $tag = isset($doneMap[$id]) ? 'DONE' : 'TODO';
                $lines[] = sprintf('  [%-4s] %-12s %s — %s', $tag, $id, $meta['title'], $meta['goal']);
            }
            $lines[] = "\nGrade an exercise against your project: kongo oshie --exercise <id>";

            return [0, implode("\n", $lines) . "\n"];
        }

        if (isset($options['exercise']) && is_string($options['exercise'])) {
            $report = $oshie->verifyExercise($options['exercise'], $context);
            if ($report['passed']) {
                $sessions->recordOshieExercise($report['exercise']);
            }
            $lines = [
                sprintf('Oshie Exercise [%s] — %s (%s)', $report['exercise'], $report['title'], $report['passed'] ? 'PASS' : 'NEEDS WORK'),
                '  Goal: ' . $report['goal'],
                '',
            ];
            foreach ($report['checks'] as $chk) {
                $lines[] = sprintf('  [%-4s] %s — %s', $chk['passed'] ? 'PASS' : 'FAIL', $chk['label'], $chk['detail']);
            }
            if (!$report['passed']) {
                $lines[] = "\nHint: " . $report['hint'];
            } else {
                $lines[] = "\nExercise verified and recorded in your Oshie progress!";
            }

            return [$report['passed'] ? 0 : 1, implode("\n", $lines) . "\n"];
        }

        if (isset($options['progress'])) {
            $progress = $sessions->oshieProgress();
            $completed = $progress['completed_lessons'];
            $completedEx = $progress['completed_exercises'];
            $doneMap = array_flip($completed);
            $all = $oshie->listLessons();
            $allEx = $oshie->listExercises();

            $lines = [
                sprintf(
                    'Kongono Oshie — Learning Progress (%d/%d lessons, %d/%d exercises verified):',
                    count($completed),
                    count($all),
                    count($completedEx),
                    count($allEx),
                ),
            ];
            foreach ($all as $id => $meta) {
                $tag = isset($doneMap[$id]) ? 'DONE' : 'TODO';
                $lines[] = sprintf('  [%-4s] %-12s %s', $tag, $id, $meta['title']);
            }
            $recs = $oshie->recommendExercises($context, $completed);
            if ([] !== $recs) {
                $lines[] = "\nRecommended next exercises for this project:";
                foreach ($recs as $rec) {
                    $lines[] = sprintf('  -> kongo oshie --lesson %s  (%s)', $rec['lesson'], $rec['reason']);
                }
            }
            $lines[] = "\nVerify hands-on exercises: kongo oshie --exercises  |  kongo oshie --exercise <id>";

            return [0, implode("\n", $lines) . "\n"];
        }

        if (isset($options['lessons'])) {
            $lines = ['Kongono Oshie — Built-in Lessons:'];
            foreach ($oshie->listLessons() as $id => $meta) {
                $lines[] = sprintf('  %-12s %s — %s', $id, $meta['title'], $meta['summary']);
            }
            $lines[] = "\nRun: kongo oshie --lesson <id>  |  kongo oshie --progress  |  kongo oshie \"<your question>\" [--stream]";

            return [0, implode("\n", $lines) . "\n"];
        }

        if (isset($options['lesson']) && is_string($options['lesson'])) {
            $lessonId = trim($options['lesson']);
            $reply = $oshie->teachLesson($lessonId, $context);
            if (array_key_exists($lessonId, $oshie->listLessons())) {
                $sessions->recordOshieLesson($lessonId);
            }

            return [0, $reply . "\n"];
        }

        $question = trim(implode(' ', $args));
        if ('' === $question) {
            return [0, $oshie->teachLesson('routing', $context) . "\n"];
        }

        if (isset($options['stream'])) {
            $writer = $this->resolveStreamWriter();
            $result = $oshie->askStream($question, $context, $writer, maxCostUsd: $maxCost);
            $tail = null !== $result['telemetry']
                ? "\n\n" . $result['telemetry']->formatFooter() . "\n"
                : "\n";

            return [0, $tail];
        }

        $result = $oshie->askWithTelemetry($question, $context, maxCostUsd: $maxCost);
        $out = $result['reply'] . "\n";
        if (null !== $result['telemetry']) {
            $out .= "\n" . $result['telemetry']->formatFooter() . "\n";
        }

        return [0, $out];
    }

    /**
     * @param list<string>               $args
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdChie(string $appRoot, array $args, array $options): array
    {
        $instruction = trim(implode(' ', $args));
        if ('' === $instruction) {
            return [1, "Usage: kongo chie \"<what to build>\" [--app=path] [--apply] [--verify] [--interactive] [--continue] [--max-repairs=2] [--rollback-on-failure]\n"];
        }

        $ai = $this->tryAiClient('chie');
        if (null === $ai) {
            return [1, "Chie requires an AI provider. Set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST.\n"];
        }

        $context = ProjectContext::discover($appRoot);
        $sessions = new CompanionSessionStore($context->rootPath);
        $previousContext = null;
        if (isset($options['continue'])) {
            $prev = $sessions->lastChieSession();
            if (null === $prev) {
                return [1, "No previous Chie session found for this project. Run `kongo chie \"<task>\"` first.\n"];
            }
            $previousContext = $prev['context_prompt'];
        }

        $builder = new ChieBuilder($ai);
        $maxCost = $this->parseMaxCost($options);
        $apply = isset($options['apply']);
        $verify = isset($options['verify']);
        $interactive = isset($options['interactive']);

        if ($verify) {
            if (!$apply) {
                return [1, "Chie --verify requires --apply (e.g. kongo chie \"<what to build>\" --apply --verify).\n"];
            }
            $maxRepairsRaw = is_string($options['max-repairs'] ?? null) && ctype_digit($options['max-repairs'])
                ? (int) $options['max-repairs']
                : 2;
            $maxRepairs = min(5, max(0, $maxRepairsRaw));
            $rollbackOnFailure = isset($options['rollback-on-failure']);

            $hantei = new HanteiDebugger($this->tryAiClient('hantei') ?? $ai);
            $verifyCmd = $this->resolveVerifyCommand($appRoot);
            $extraGates = $this->resolveExtraVerifyCommands($appRoot);
            $report = $builder->buildAndVerify(
                instruction: $instruction,
                context: $context,
                hantei: $hantei,
                verifyCommand: $verifyCmd,
                maxRepairs: $maxRepairs,
                maxCostUsd: $maxCost,
                rollbackOnFailure: $rollbackOnFailure,
                extraVerifyCommands: $extraGates,
                previousContext: $previousContext,
            );
            $sessions->saveChiePlan($instruction, $report['summary'], $report['changes'], $report['rolled_back'] ? [] : $report['applied']);

            $lines = [
                sprintf(
                    'Chie Self-Verifying Build (%s / %s): %s [%s after %d attempt(s)]',
                    $report['provider'],
                    $report['model'],
                    $report['summary'],
                    $report['verified'] ? 'VERIFIED' : ($report['rolled_back'] ? 'FAILED — ROLLED BACK' : 'FAILED'),
                    $report['attempts'],
                ),
            ];
            if ([] !== $report['gates']) {
                $gateLabels = array_map(
                    static fn(array $g): string => sprintf('%s:%s', $g['name'], $g['passed'] ? 'PASS' : 'FAIL'),
                    $report['gates'],
                );
                $lines[] = 'Gates: ' . implode(' | ', $gateLabels);
            }
            $lines[] = '';
            foreach ($report['changes'] as $change) {
                $status = $report['rolled_back'] ? 'ROLLED BACK' : 'WRITTEN';
                $lines[] = sprintf('[%s] (%s) %s — %s', $status, $change['action'], $change['path'], $change['explanation']);
            }
            if (!$report['verified'] && '' !== trim($report['verify_output'])) {
                $lines[] = "\n--- Verification Output ---";
                $lines[] = $report['verify_output'];
            }
            if ([] !== $report['next_steps']) {
                $lines[] = "\nNext steps:";
                foreach ($report['next_steps'] as $step) {
                    $lines[] = '  - ' . $step;
                }
            }
            $lines[] = "\n" . $report['telemetry']->formatFooter();

            return [$report['verified'] ? 0 : 1, implode("\n", $lines) . "\n"];
        }

        if ($interactive) {
            return $this->runChieInteractive($builder, $instruction, $context, $maxCost, $sessions, $previousContext);
        }

        $result = $builder->build($instruction, $context, $apply, $maxCost, $previousContext);
        $sessions->saveChiePlan($instruction, $result['summary'], $result['changes'], $result['applied']);

        $lines = [
            sprintf('Chie (%s / %s): %s', $result['provider'], $result['model'], $result['summary']),
            '',
        ];
        foreach ($result['changes'] as $change) {
            $status = $apply ? 'WRITTEN' : 'PREVIEW';
            $lines[] = sprintf('[%s] (%s) %s — %s', $status, $change['action'], $change['path'], $change['explanation']);
            if (!$apply) {
                $before = null;
                try {
                    $before = $context->readFileSafe($change['path']);
                } catch (Throwable) {
                    $before = null;
                }
                $lines[] = DiffRenderer::render($change['path'], $before, SecretScrubber::scrub($change['content']));
                $lines[] = '';
            }
        }
        if (!$apply && [] !== $result['changes']) {
            $lines[] = "\nRe-run with --apply, --apply --verify, --interactive, or --continue to iterate.";
        }
        if ([] !== $result['next_steps']) {
            $lines[] = "\nNext steps:";
            foreach ($result['next_steps'] as $step) {
                $lines[] = '  - ' . $step;
            }
        }
        $lines[] = "\n" . $result['telemetry']->formatFooter();

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function runChieInteractive(
        ChieBuilder $builder,
        string $instruction,
        ProjectContext $context,
        ?float $maxCostUsd,
        CompanionSessionStore $sessions,
        ?string $previousContext = null,
    ): array {
        $result = $builder->build($instruction, $context, apply: false, maxCostUsd: $maxCostUsd, previousContext: $previousContext);
        $lines = [
            sprintf('Chie Interactive Review (%s / %s): %s', $result['provider'], $result['model'], $result['summary']),
            '',
        ];

        if ([] === $result['changes']) {
            $lines[] = 'No file changes proposed.';
            $lines[] = "\n" . $result['telemetry']->formatFooter();

            return [0, implode("\n", $lines) . "\n"];
        }

        /** @var list<string> $approvedPaths */
        $approvedPaths = [];
        $approveAll = false;
        $aborted = false;

        foreach ($result['changes'] as $change) {
            $before = null;
            try {
                $before = $context->readFileSafe($change['path']);
            } catch (Throwable) {
                $before = null;
            }
            $diff = DiffRenderer::render($change['path'], $before, SecretScrubber::scrub($change['content']));

            if ($approveAll) {
                $approvedPaths[] = $change['path'];
                continue;
            }

            $prompt = sprintf(
                "\n--- [%s] %s — %s ---\n%s\nApply %s? [y]es / [n]o / [a]ll / [q]uit: ",
                $change['action'],
                $change['path'],
                $change['explanation'],
                $diff,
                $change['path'],
            );
            $answer = $this->readInteractiveLine($prompt);
            $choice = strtolower(trim($answer ?? 'q'));

            if ('q' === $choice || 'quit' === $choice) {
                $aborted = true;
                break;
            }
            if ('a' === $choice || 'all' === $choice) {
                $approveAll = true;
                $approvedPaths[] = $change['path'];
                continue;
            }
            if ('y' === $choice || 'yes' === $choice) {
                $approvedPaths[] = $change['path'];
            }
        }

        if ($aborted) {
            $lines[] = 'Aborted by user — no files written.';
            $lines[] = "\n" . $result['telemetry']->formatFooter();

            return [0, implode("\n", $lines) . "\n"];
        }

        $applied = [] !== $approvedPaths
            ? $builder->applyChanges($result['changes'], $context, $approvedPaths)
            : [];
        $sessions->saveChiePlan($instruction, $result['summary'], $result['changes'], $applied);

        $appliedSet = array_flip($approvedPaths);
        foreach ($result['changes'] as $change) {
            $status = isset($appliedSet[$change['path']]) ? 'WRITTEN' : 'SKIPPED';
            $lines[] = sprintf('[%s] (%s) %s — %s', $status, $change['action'], $change['path'], $change['explanation']);
        }
        $lines[] = sprintf("\nApplied %d of %d proposed file(s).", count($applied), count($result['changes']));
        if ([] !== $result['next_steps']) {
            $lines[] = "\nNext steps:";
            foreach ($result['next_steps'] as $step) {
                $lines[] = '  - ' . $step;
            }
        }
        $lines[] = "\n" . $result['telemetry']->formatFooter();

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param list<string>               $args
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdHantei(string $appRoot, array $args, array $options): array
    {
        $sub = $args[0] ?? 'check';
        $rest = array_slice($args, 1);
        $context = ProjectContext::discover($appRoot);
        $sessions = new CompanionSessionStore($context->rootPath);
        $hanteiAi = $this->tryAiClient('hantei');
        $hantei = new HanteiDebugger($hanteiAi);
        $maxCost = $this->parseMaxCost($options);

        if ('watch' === $sub) {
            return $this->runHanteiWatch($appRoot, $context, $hantei, $sessions, $options, $maxCost);
        }

        if ('history' === $sub) {
            $limitRaw = is_string($options['limit'] ?? null) && ctype_digit($options['limit']) ? (int) $options['limit'] : 10;
            $items = $sessions->hanteiHistory(max(1, min(25, $limitRaw)));
            if ([] === $items) {
                return [0, "No Hantei diagnosis history recorded yet for this project.\n"];
            }
            $lines = [sprintf('Hantei Diagnosis History (%d most recent):', count($items))];
            foreach ($items as $idx => $item) {
                $lines[] = sprintf(
                    '  %d. [%s] (%s) %s — %s',
                    $idx + 1,
                    $item['timestamp'],
                    $item['kind'],
                    $item['subject'],
                    str_replace(["\r", "\n"], ' ', substr($item['diagnosis_excerpt'], 0, 120)),
                );
            }

            return [0, implode("\n", $lines) . "\n"];
        }

        if ('review' === $sub) {
            $base = is_string($options['base'] ?? null) && '' !== trim($options['base']) ? trim($options['base']) : 'main';
            $format = is_string($options['format'] ?? null) ? strtolower(trim($options['format'])) : 'markdown';
            $noAi = isset($options['no-ai']);
            $reviewer = $noAi ? new HanteiDebugger(null) : $hantei;

            $diff = $this->collectGitDiff($context->rootPath, $base);
            $report = $reviewer->reviewDiff($diff, $context, $maxCost);
            $sessions->recordHanteiDiagnosis(
                'review',
                'git diff vs ' . $base,
                sprintf('passed=%s critical=%d warning=%d', $report['passed'] ? 'true' : 'false', $report['counts']['critical'], $report['counts']['warning']),
                $report['telemetry']?->provider,
            );

            $output = 'sarif' === $format
                ? HanteiDebugger::formatReviewSarif($report)
                : HanteiDebugger::formatReviewMarkdown($report);

            return [$report['passed'] ? 0 : 1, $output];
        }

        if ('fix' === $sub) {
            $chieAi = $this->tryAiClient('chie') ?? $hanteiAi;
            if (null === $chieAi) {
                return [1, "Hantei fix requires an AI provider. Set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST.\n"];
            }
            $apply = isset($options['apply']);
            $verify = isset($options['verify']);
            if ($verify && !$apply) {
                return [1, "Hantei fix --verify requires --apply (e.g. kongo hantei fix --apply --verify).\n"];
            }

            $verifyCmd = $this->resolveVerifyCommand($appRoot);
            $failureInput = trim(implode(' ', $rest));
            if ('' === $failureInput) {
                $check = $hantei->runCheck($verifyCmd, $context, diagnoseWithAi: false);
                if (0 === $check['exit_code']) {
                    return [0, "All checks already pass — nothing for Hantei to fix.\n"];
                }
                $failureInput = $check['output'];
            }

            $maxRepairsRaw = is_string($options['max-repairs'] ?? null) && ctype_digit($options['max-repairs'])
                ? (int) $options['max-repairs']
                : 2;
            $report = $hantei->diagnoseAndFix(
                failureText: $failureInput,
                context: $context,
                chie: new ChieBuilder($chieAi),
                verifyCommand: $verifyCmd,
                apply: $apply,
                verify: $verify,
                maxRepairs: min(5, max(0, $maxRepairsRaw)),
                maxCostUsd: $maxCost,
                rollbackOnFailure: isset($options['rollback-on-failure']),
                extraVerifyCommands: $this->resolveExtraVerifyCommands($appRoot),
            );
            $sessions->recordHanteiDiagnosis('fix', substr($failureInput, 0, 120), $report['diagnosis'], $report['telemetry']->provider);
            $sessions->saveChiePlan('Hantei fix: ' . substr($failureInput, 0, 120), $report['summary'], $report['changes'], $report['rolled_back'] ? [] : $report['applied']);

            $lines = [
                '--- Hantei Root-Cause Diagnosis ---',
                $report['diagnosis'],
                '',
                sprintf('--- Chie Patch Plan: %s ---', $report['summary']),
            ];
            foreach ($report['changes'] as $change) {
                $status = $verify ? ($report['rolled_back'] ? 'ROLLED BACK' : 'WRITTEN') : ($apply ? 'WRITTEN' : 'PREVIEW');
                $lines[] = sprintf('[%s] (%s) %s — %s', $status, $change['action'], $change['path'], $change['explanation']);
                if (!$apply) {
                    $before = null;
                    try {
                        $before = $context->readFileSafe($change['path']);
                    } catch (Throwable) {
                        $before = null;
                    }
                    $lines[] = DiffRenderer::render($change['path'], $before, SecretScrubber::scrub($change['content']));
                }
            }
            if (!$apply && [] !== $report['changes']) {
                $lines[] = "\nRe-run with --apply or --apply --verify to write and verify this fix.";
            }
            $lines[] = "\n" . $report['telemetry']->formatFooter();

            $exitCode = ($verify && !$report['verified']) ? 1 : 0;

            return [$exitCode, implode("\n", $lines) . "\n"];
        }

        if ('debug' === $sub) {
            $input = trim(implode(' ', $rest));
            if ('' === $input) {
                return [1, "Usage: kongo hantei debug \"<error message or diagnostic>\" [--stream]\n"];
            }

            if (isset($options['stream'])) {
                $writer = $this->resolveStreamWriter();
                $res = $hantei->diagnoseStream($input, $context, $writer, $maxCost);
                $sessions->recordHanteiDiagnosis('debug', $input, $res['diagnosis'], $res['telemetry']?->provider);
                $tail = null !== $res['telemetry']
                    ? "\n\n" . $res['telemetry']->formatFooter() . "\n"
                    : "\n";

                return [0, $tail];
            }

            $res = $hantei->diagnoseWithTelemetry($input, $context, $maxCost);
            $sessions->recordHanteiDiagnosis('debug', $input, $res['diagnosis'], $res['telemetry']?->provider);
            $out = $res['diagnosis'] . "\n";
            if (null !== $res['telemetry']) {
                $out .= "\n" . $res['telemetry']->formatFooter() . "\n";
            }

            return [0, $out];
        }

        if ('queue-failed' === $sub) {
            $driver = $this->resolveQueueDriver($appRoot, $error);
            if (null === $driver) {
                return [1, $error ?? "Failed to resolve queue driver.\n"];
            }
            $queueOpt = $options['queue'] ?? 'default';
            $queue = is_string($queueOpt) && '' !== trim($queueOpt) ? trim($queueOpt) : 'default';
            $failed = $driver->listFailed($queue, 50);
            if ([] === $failed) {
                return [0, sprintf("No failed jobs found on queue \"%s\".\n", $queue)];
            }

            $targetId = isset($rest[0]) && '' !== trim($rest[0]) ? trim($rest[0]) : null;
            $selected = null;
            if (null === $targetId) {
                $selected = $failed[0];
            } else {
                foreach ($failed as $job) {
                    if ($job->id === $targetId) {
                        $selected = $job;
                        break;
                    }
                }
            }

            if (null === $selected) {
                return [1, sprintf("Failed job \"%s\" not found on queue \"%s\".\n", $targetId, $queue)];
            }

            $diag = $hantei->diagnoseFailedJob($selected, $context, $maxCost);
            $sessions->recordHanteiDiagnosis('queue-failed', $diag['job_type'] . ' #' . $diag['job_id'], $diag['diagnosis'], $diag['telemetry']?->provider);
            $lines = [
                sprintf(
                    'Hantei Dead-Letter Job Diagnosis — #%s (%s) on queue "%s" [attempts: %d/%d]:',
                    $diag['job_id'],
                    $diag['job_type'],
                    $diag['queue'],
                    $selected->attempt,
                    $selected->maxAttempts,
                ),
                '',
                $diag['diagnosis'],
            ];
            if (null !== $diag['telemetry']) {
                $lines[] = "\n" . $diag['telemetry']->formatFooter();
            }

            return [0, implode("\n", $lines) . "\n"];
        }

        if ('generate-tests' === $sub) {
            $target = $rest[0] ?? '';
            if ('' === $target) {
                return [1, "Usage: kongo hantei generate-tests <relative/source/file.php> [--apply]\n"];
            }
            $result = $hantei->generateTests($target, $context, apply: isset($options['apply']));
            $header = $result['written']
                ? sprintf("Hantei wrote tests to %s\n", $result['target_path'])
                : sprintf("Hantei generated tests for %s (preview; pass --apply to write):\n\n", $result['target_path']);

            return [0, $header . $result['code']];
        }

        if ('check' === $sub || 'test' === $sub) {
            $phpunit = $this->workingDirectory . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit';
            $cmd = [PHP_BINARY, $phpunit];
            $report = $hantei->runCheck($cmd, new ProjectContext($this->workingDirectory, $context->graph));
            $out = $report['output'] . "\n";
            if (null !== $report['diagnosis']) {
                $sessions->recordHanteiDiagnosis('check', 'phpunit', $report['diagnosis'], $hanteiAi?->providerName());
                $out .= "\n--- Hantei AI Diagnosis ---\n" . $report['diagnosis'] . "\n";
            }

            return [$report['exit_code'], $out];
        }

        return [1, "Unknown hantei subcommand. Use: check | debug \"<error>\" [--stream] | fix [\"<error>\"] [--apply] [--verify] | review [--base=main] [--format=markdown|sarif] | watch [--log=file] [--queue=default] | history | queue-failed [<job-id>] | generate-tests <file>\n"];
    }

    /**
     * Local dev watcher inspecting newly dead-lettered jobs or newly logged errors and printing
     * secret-scrubbed Hantei root-cause diagnoses as they occur.
     *
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function runHanteiWatch(
        string $appRoot,
        ProjectContext $context,
        HanteiDebugger $hantei,
        CompanionSessionStore $sessions,
        array $options,
        ?float $maxCost,
    ): array {
        $logOpt = is_string($options['log'] ?? null) && '' !== trim($options['log']) ? trim($options['log']) : null;
        $queueOpt = is_string($options['queue'] ?? null) && '' !== trim($options['queue']) ? trim($options['queue']) : 'default';
        $interval = is_string($options['interval'] ?? null) && ctype_digit($options['interval'])
            ? max(1, min(60, (int) $options['interval']))
            : 2;
        $once = isset($options['once']);
        $maxCycles = $once
            ? 1
            : (is_string($options['max-cycles'] ?? null) && ctype_digit($options['max-cycles']) ? max(1, (int) $options['max-cycles']) : 0);

        $lines = [];
        $diagnosedCount = 0;

        if (null !== $logOpt) {
            // Validate contained readable path (never allows .env, keys, or traversal) and tail from EOF
            $initialTail = $context->readFileTailSafe($logOpt, 131072, null);
            $offset = $initialTail['offset'];
            $cycle = 0;

            while (0 === $maxCycles || $cycle < $maxCycles) {
                ++$cycle;
                if (1 === $cycle) {
                    $chunk = $initialTail['content'];
                } else {
                    $tail = $context->readFileTailSafe($logOpt, 131072, $offset);
                    $chunk = $tail['content'];
                    $offset = $tail['offset'];
                }

                if ('' !== trim($chunk)) {
                    $errorLines = [];
                    $splitLines = preg_split("/\r\n|\n|\r/", $chunk);
                    foreach (false !== $splitLines ? $splitLines : [] as $logLine) {
                        if (1 === preg_match('/\b(?:ERROR|CRITICAL|EMERGENCY|ALERT|FATAL|EXCEPTION|KGN-)\b|Stack trace:/i', $logLine)) {
                            $errorLines[] = SecretScrubber::scrub(trim($logLine));
                        }
                    }
                    if ([] !== $errorLines) {
                        $excerpt = implode("\n", array_slice($errorLines, -15));
                        $diag = $hantei->diagnoseWithTelemetry($excerpt, $context, $maxCost);
                        $sessions->recordHanteiDiagnosis('watch-log', $logOpt, $diag['diagnosis'], $diag['telemetry']?->provider);
                        ++$diagnosedCount;

                        $block = sprintf(
                            "[Hantei Watch — %s (%d error line(s))]\n%s%s\n",
                            $logOpt,
                            count($errorLines),
                            $diag['diagnosis'],
                            null !== $diag['telemetry'] ? "\n" . $diag['telemetry']->formatFooter() : '',
                        );
                        $lines[] = $block;
                        if (!$once) {
                            fwrite(STDOUT, $block . "\n");
                        }
                    }
                }

                if (0 !== $maxCycles && $cycle >= $maxCycles) {
                    break;
                }
                sleep($interval);
            }

            if (0 === $diagnosedCount) {
                return [0, sprintf("Hantei Watch inspected \"%s\": 0 error lines found.\n", $logOpt)];
            }

            return [0, implode("\n", $lines)];
        }

        // Queue DLQ watch mode
        $driver = $this->resolveQueueDriver($appRoot, $error);
        if (null === $driver) {
            return [1, ($error ?? "No queue driver configured.\n") . "Tip: pass --log=<relative/path.log> to watch a log file instead.\n"];
        }

        /** @var array<string, true> $seenIds */
        $seenIds = [];
        $cycle = 0;
        while (0 === $maxCycles || $cycle < $maxCycles) {
            ++$cycle;
            foreach ($driver->listFailed($queueOpt, 20) as $job) {
                if (isset($seenIds[$job->id])) {
                    continue;
                }
                $seenIds[$job->id] = true;
                $diag = $hantei->diagnoseFailedJob($job, $context, $maxCost);
                $sessions->recordHanteiDiagnosis('watch-queue', $job->type . ' #' . $job->id, $diag['diagnosis'], $diag['telemetry']?->provider);
                ++$diagnosedCount;

                $block = sprintf(
                    "[Hantei Watch — Failed Job #%s (%s) on queue \"%s\"]\n%s%s\n",
                    $job->id,
                    $job->type,
                    $job->queue,
                    $diag['diagnosis'],
                    null !== $diag['telemetry'] ? "\n" . $diag['telemetry']->formatFooter() : '',
                );
                $lines[] = $block;
                if (!$once) {
                    fwrite(STDOUT, $block . "\n");
                }
            }

            if (0 !== $maxCycles && $cycle >= $maxCycles) {
                break;
            }
            sleep($interval);
        }

        if (0 === $diagnosedCount) {
            return [0, sprintf("Hantei Watch inspected queue \"%s\": 0 newly failed jobs.\n", $queueOpt)];
        }

        return [0, implode("\n", $lines)];
    }

    /**
     * Run Kongono's framework-native self-inspector (`kongo diagnose [--app=...] [--json] [--strict]`).
     *
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdDiagnose(string $appRoot, array $options): array
    {
        $fixReport = null;
        if (isset($options['fix'])) {
            $fixReport = (new AppAutoFixer())->fixDirectory($appRoot, dryRun: isset($options['dry-run']));
        }

        $app = null;
        $bootError = null;
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (is_file($bootstrap)) {
            try {
                $loaded = require $bootstrap;
                if ($loaded instanceof App) {
                    $app = $loaded;
                }
            } catch (Throwable $e) {
                $bootError = (new ErrorExplainer())->explainThrowable($e, basePath: $appRoot)->toArray();
            }
        }

        $report = (new KongonoAppInspector())->inspect($appRoot, $app);
        if (null !== $fixReport) {
            $report['autofix'] = $fixReport;
        }
        if (null !== $bootError) {
            $report['boot_error'] = $bootError;
            $report['passed'] = false;
            $report['score'] = 0;
        }

        $strict = isset($options['strict']);
        $failed = !$report['passed'] || ($strict && $report['counts']['warning'] > 0);

        if (isset($options['json'])) {
            return [$failed ? 1 : 0, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [];
        if (null !== $fixReport) {
            $modeLabel = $fixReport['dry_run'] ? 'Autofix dry-run preview' : 'Autofix applied';
            $lines[] = sprintf(
                '%s: %d fix(es) across %d file(s).',
                $modeLabel,
                $fixReport['total_fixes'],
                $fixReport['files_changed'],
            );
            foreach ($fixReport['changes'] as $change) {
                $lines[] = '';
                $lines[] = $change['diff'];
            }
            $lines[] = '';
        }

        $lines[] = sprintf('Kongono Self-Diagnosis — Score: %d/100 (%s)', $report['score'], $failed ? 'FAILED' : 'PASS');
        $lines[] = sprintf(
            '  Checked: %d route(s), %d view(s), %d PHP file(s) | Errors: %d, Warnings: %d',
            $report['routes_checked'],
            $report['views_checked'],
            $report['php_files_checked'],
            $report['counts']['error'],
            $report['counts']['warning'],
        );

        if (null !== $bootError) {
            $lines[] = '';
            $lines[] = rtrim($this->formatCliExplanation($bootError));
        }

        if ([] === $report['findings'] && null === $bootError) {
            $lines[] = '  [OK] Zero Kongono framework, DI, Live 2.5, template, CSRF, route, migration, or persistent-worker issues detected.';
        } else {
            foreach ($report['findings'] as $f) {
                $lines[] = '';
                $lines[] = sprintf(
                    '  [%-7s] %s (%s)%s',
                    strtoupper($f['severity']),
                    $f['code'],
                    $f['where'],
                    $f['fixable'] ? ' [auto-fixable]' : '',
                );
                $lines[] = sprintf('    What:  %s', $f['what']);
                $lines[] = sprintf('    Why:   %s', $f['why']);
                $lines[] = sprintf('    Who:   %s', $f['who']);
                if ([] !== $f['did_you_mean']) {
                    $lines[] = sprintf('    Did you mean: %s', implode(', ', $f['did_you_mean']));
                }
                foreach ($f['how_to_fix'] as $idx => $step) {
                    $lines[] = sprintf('    Fix %d: %s', $idx + 1, $step);
                }
                if (null !== $f['command'] && '' !== $f['command']) {
                    $lines[] = sprintf('    Run:   %s', $f['command']);
                }
            }
            if ($report['fixable_count'] > 0 && !isset($options['fix'])) {
                $lines[] = '';
                $lines[] = sprintf(
                    '  Tip: %d issue(s) can be fixed automatically with `php vendor/bin/kongo diagnose --fix` (add `--dry-run` to preview diffs).',
                    $report['fixable_count'],
                );
            }
        }

        return [$failed ? 1 : 0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdProve(string $appRoot, array $options): array
    {
        $app = $this->tryLoadBootstrappedApp($appRoot);
        $report = (new MasterProver())->proveAll($appRoot, $app);
        $strict = isset($options['strict']);
        $failed = !$report['passed'] || ($strict && $report['total_warnings'] > 0);

        if (isset($options['json'])) {
            return [$failed ? 1 : 0, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [
            sprintf(
                'Kongono Readiness Certificate — %s — Grade: %s (%d/100 %s) [%.1fms]',
                $report['app_name'],
                $report['grade'],
                $report['overall_score'],
                $failed ? 'FAILED' : 'PASS',
                $report['elapsed_ms'],
            ),
            str_repeat('=', 84),
        ];

        foreach ($report['pillars'] as $pillar) {
            $lines[] = sprintf(
                '  [%-4s] %3d/100  %-44s %s',
                $pillar['passed'] ? 'PASS' : 'FAIL',
                $pillar['score'],
                $pillar['label'],
                $pillar['summary'],
            );
        }

        $lines[] = str_repeat('=', 84);
        $lines[] = sprintf(
            '  Totals: %d error(s), %d warning(s), %d auto-fixable | Routes: %d | Recorded incidents: %d',
            $report['total_errors'],
            $report['total_warnings'],
            $report['fixable_count'],
            count($report['route_capabilities']),
            count($report['incidents']),
        );

        foreach ($report['pillars'] as $pillar) {
            if (!isset($pillar['findings']) || [] === $pillar['findings']) {
                continue;
            }
            foreach ($pillar['findings'] as $f) {
                $sev = is_string($f['severity'] ?? null) ? strtoupper($f['severity']) : 'ERROR';
                $code = is_string($f['code'] ?? null) ? $f['code'] : 'KGN';
                $where = is_string($f['where'] ?? null) ? $f['where'] : '';
                $what = is_string($f['what'] ?? null) ? $f['what'] : '';
                $lines[] = '';
                $lines[] = sprintf('  [%-7s] %s (%s): %s', $sev, $code, $where, $what);
            }
        }

        return [$failed ? 1 : 0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdPolicyCheck(string $appRoot, array $options): array
    {
        $app = $this->tryLoadBootstrappedApp($appRoot);
        $report = (new PolicyVerifier())->verify($appRoot, $app, requireApp: true);

        if (isset($options['json'])) {
            return [$report['passed'] ? 0 : 1, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [
            sprintf('Kongono Architecture & Security Policy — Score: %d/100 (%s)', $report['score'], $report['passed'] ? 'PASS' : 'FAILED'),
            sprintf(
                '  Rules evaluated: %d (%s) | Violations: %d error(s), %d warning(s)',
                $report['rules_evaluated'],
                $report['custom_policy_loaded'] ? 'defaults + app/invariants.php' : 'built-in defaults',
                $report['counts']['error'],
                $report['counts']['warning'],
            ),
        ];

        if ([] === $report['violations']) {
            $lines[] = '  [OK] All architecture and security invariants verified.';
        } else {
            foreach ($report['violations'] as $v) {
                $lines[] = '';
                $lines[] = sprintf('  [%-7s] %s (%s)', strtoupper($v['severity']), $v['code'], $v['where']);
                $lines[] = sprintf('    What:  %s', $v['what']);
                $lines[] = sprintf('    Why:   %s', $v['why']);
                foreach ($v['how_to_fix'] as $idx => $step) {
                    $lines[] = sprintf('    Fix %d: %s', $idx + 1, $step);
                }
            }
        }

        return [$report['passed'] ? 0 : 1, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdPerf(string $appRoot, array $options): array
    {
        $app = $this->tryLoadBootstrappedApp($appRoot);
        if (null === $app) {
            return [1, sprintf("No runnable Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $iterations = is_string($options['iterations'] ?? null) && is_numeric($options['iterations'])
            ? max(1, (int) $options['iterations'])
            : 3;
        $maxMs = is_string($options['max-ms'] ?? null) && is_numeric($options['max-ms'])
            ? max(1.0, (float) $options['max-ms'])
            : 250.0;
        $paths = is_string($options['path'] ?? null) && '' !== trim($options['path'])
            ? [trim($options['path'])]
            : null;

        $report = (new PerfProver())->prove($app, $paths, $iterations, $maxMs);

        if (isset($options['json'])) {
            return [$report['passed'] ? 0 : 1, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [
            sprintf('Kongono Persistent-Worker & N+1 Proof — Score: %d/100 (%s)', $report['score'], $report['passed'] ? 'PASS' : 'FAILED'),
            sprintf(
                '  Probed: %d route(s) × %d iter(s) | Queries: %d | N+1: %d | Leaks: %d | Budget: %.0fms',
                $report['routes_probed'],
                $report['iterations'],
                $report['total_queries'],
                $report['n_plus_one_detected'],
                $report['leaks_detected'],
                $report['max_ms_budget'],
            ),
            str_repeat('-', 84),
            sprintf('  %-28s %-6s %-9s %-9s %-8s %-10s', 'PATH', 'STATUS', 'P50(ms)', 'P95(ms)', 'QUERIES', 'MEM/REQ'),
            str_repeat('-', 84),
        ];

        foreach ($report['routes'] as $r) {
            $lines[] = sprintf(
                '  %-28s %-6d %-9.2f %-9.2f %-8d %d KB',
                $r['path'],
                $r['status'],
                $r['p50_ms'],
                $r['p95_ms'],
                $r['queries_per_req'],
                (int) round($r['bytes_per_req'] / 1024),
            );
        }

        if ([] !== $report['findings']) {
            foreach ($report['findings'] as $f) {
                $lines[] = '';
                $lines[] = sprintf('  [%-7s] %s (%s)', strtoupper($f['severity']), $f['code'], $f['where']);
                $lines[] = sprintf('    What:  %s', $f['what']);
                $lines[] = sprintf('    Why:   %s', $f['why']);
                foreach ($f['how_to_fix'] as $idx => $step) {
                    $lines[] = sprintf('    Fix %d: %s', $idx + 1, $step);
                }
            }
        } else {
            $lines[] = '  [OK] Zero persistent-worker leaks, zero SQL N+1 loops, and all routes within latency budget.';
        }

        return [$report['passed'] ? 0 : 1, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdIncidentList(string $appRoot, array $options): array
    {
        $app = $this->tryLoadBootstrappedApp($appRoot);
        $store = (new IncidentReplayer())->resolveStore($appRoot, $app);
        $limit = is_string($options['limit'] ?? null) && is_numeric($options['limit']) ? max(1, (int) $options['limit']) : 20;
        $records = $store->recent($limit);

        if (isset($options['json'])) {
            return [0, json_encode(array_map(static fn($r): array => $r->toArray(), $records), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        if ([] === $records) {
            return [0, "No recorded incidents found (enable with `\$app->recordIncidents()` in app/bootstrap.php).\n"];
        }

        $lines = [
            sprintf('Recorded Kongono Incidents (%d shown):', count($records)),
            str_repeat('-', 86),
            sprintf('  %-22s %-6s %-7s %-24s %s', 'REFERENCE', 'STATUS', 'METHOD', 'PATH', 'CODE'),
            str_repeat('-', 86),
        ];
        foreach ($records as $r) {
            $code = is_string($r->explanation['code'] ?? null) ? $r->explanation['code'] : '-';
            $lines[] = sprintf('  %-22s %-6d %-7s %-24s %s', $r->reference, $r->statusCode, $r->method, $r->path, $code);
        }

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param list<string>               $args
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdIncidentShow(string $appRoot, array $args, array $options): array
    {
        $ref = trim($args[0] ?? '');
        if ('' === $ref) {
            return [1, "Usage: kongo incident:show <KGN-YYYYMMDD-XXXXXXXX> [--app=path] [--json]\n"];
        }

        $app = $this->tryLoadBootstrappedApp($appRoot);
        $store = (new IncidentReplayer())->resolveStore($appRoot, $app);
        $record = $store->find($ref);
        if (null === $record) {
            return [1, sprintf("Incident \"%s\" not found.\n", $ref)];
        }

        if (isset($options['json'])) {
            return [0, json_encode($record->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $exp = $record->explanation;
        $lines = [
            sprintf('Kongono Incident %s (HTTP %d — %s %s)', $record->reference, $record->statusCode, $record->method, $record->path),
            sprintf('  Occurred: %s | App: %s', $record->occurredAt, $record->appName),
            sprintf('  Code:     %s', is_string($exp['code'] ?? null) ? $exp['code'] : '-'),
            sprintf('  What:     %s', is_string($exp['what'] ?? null) ? $exp['what'] : '-'),
            sprintf('  Why:      %s', is_string($exp['why'] ?? null) ? $exp['why'] : '-'),
            sprintf('  Who:      %s', is_string($exp['who'] ?? null) ? $exp['who'] : '-'),
            sprintf('  Where:    %s', is_string($exp['where'] ?? null) ? $exp['where'] : '-'),
        ];
        if (is_array($exp['how_to_fix'] ?? null)) {
            $lines[] = '  How to fix:';
            foreach ($exp['how_to_fix'] as $idx => $step) {
                if (is_string($step)) {
                    $lines[] = sprintf('    %d. %s', ((int) $idx) + 1, $step);
                }
            }
        }
        $lines[] = sprintf('  Replay:   php vendor/bin/kongo incident:replay %s', $record->reference);
        $lines[] = sprintf('  Test:     php vendor/bin/kongo incident:test %s --apply', $record->reference);

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param list<string>               $args
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdIncidentReplay(string $appRoot, array $args, array $options): array
    {
        $ref = trim($args[0] ?? '');
        if ('' === $ref) {
            return [1, "Usage: kongo incident:replay <KGN-YYYYMMDD-XXXXXXXX> [--app=path] [--json]\n"];
        }

        $app = $this->tryLoadBootstrappedApp($appRoot);
        if (null === $app) {
            return [1, sprintf("No runnable Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $replayer = new IncidentReplayer();
        $record = $replayer->resolveStore($appRoot, $app)->find($ref);
        if (null === $record) {
            return [1, sprintf("Incident \"%s\" not found.\n", $ref)];
        }

        $result = $replayer->replay($app, $record);

        if (isset($options['json'])) {
            return [$result['resolved'] ? 0 : 1, json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [
            sprintf(
                'Incident Replay %s (%s %s) — %s',
                $result['reference'],
                $result['method'],
                $result['path'],
                $result['resolved'] ? 'RESOLVED' : 'STILL FAILING',
            ),
            sprintf(
                '  Status: HTTP %d -> HTTP %d (%.2fms)',
                $result['original_status'],
                $result['replayed_status'],
                $result['elapsed_ms'],
            ),
        ];
        if ($result['resolved']) {
            $lines[] = sprintf('  Lock in this fix with: php vendor/bin/kongo incident:test %s --apply', $result['reference']);
        } elseif ('' !== $result['response_preview']) {
            $lines[] = '  Response preview: ' . $result['response_preview'];
        }

        return [$result['resolved'] ? 0 : 1, implode("\n", $lines) . "\n"];
    }

    /**
     * @param list<string>               $args
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdIncidentTest(string $appRoot, array $args, array $options): array
    {
        $ref = trim($args[0] ?? '');
        if ('' === $ref) {
            return [1, "Usage: kongo incident:test <KGN-YYYYMMDD-XXXXXXXX> [--app=path] [--apply]\n"];
        }

        $app = $this->tryLoadBootstrappedApp($appRoot);
        $record = (new IncidentReplayer())->resolveStore($appRoot, $app)->find($ref);
        if (null === $record) {
            return [1, sprintf("Incident \"%s\" not found.\n", $ref)];
        }

        $generated = (new IncidentTestGenerator())->generate($record);
        if (isset($options['apply'])) {
            $this->writeSafeCliFile($appRoot, $generated['relative_path'], $generated['content']);

            return [0, sprintf("Generated regression test: %s\n", $generated['relative_path'])];
        }

        return [0, sprintf("# Preview: %s (pass --apply to write)\n\n%s", $generated['relative_path'], $generated['content'])];
    }

    private function tryLoadBootstrappedApp(string $appRoot): ?App
    {
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            return null;
        }

        try {
            $loaded = require $bootstrap;

            return $loaded instanceof App ? $loaded : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdProveIsolation(string $appRoot, array $options): array
    {
        $app = $this->tryLoadBootstrappedApp($appRoot);
        if (null === $app) {
            return [1, sprintf("No runnable Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $report = (new TenantIsolationProver())->prove($appRoot, $app);
        if (isset($options['json'])) {
            return [$report['passed'] ? 0 : 1, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [
            sprintf('Kongono Multi-Tenant Isolation Proof — Score: %d/100 (%s)', $report['score'], $report['passed'] ? 'PASS' : 'FAILED'),
            sprintf(
                '  Protected routes probed: %d | Cross-tenant probes: %d | Leaks detected: %d',
                $report['protected_routes_probed'],
                $report['tenant_probes_executed'],
                $report['leaks_detected'],
            ),
        ];

        if ([] === $report['findings']) {
            $lines[] = '  [OK] Zero unauthenticated exposures or cross-tenant BOLA/IDOR leaks detected.';
        } else {
            foreach ($report['findings'] as $f) {
                $lines[] = '';
                $lines[] = sprintf('  [%-7s] %s (%s)', strtoupper($f['severity']), $f['code'], $f['where']);
                $lines[] = sprintf('    What:  %s', $f['what']);
                $lines[] = sprintf('    Why:   %s', $f['why']);
                foreach ($f['how_to_fix'] as $idx => $step) {
                    $lines[] = sprintf('    Fix %d: %s', $idx + 1, $step);
                }
            }
        }

        return [$report['passed'] ? 0 : 1, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdSnapshot(string $appRoot, array $options): array
    {
        $app = $this->tryLoadBootstrappedApp($appRoot);
        if (null === $app) {
            return [1, sprintf("No runnable Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $snapshot = AppSnapshot::capture($appRoot, $app);
        $outOpt = is_string($options['out'] ?? null) && '' !== trim($options['out'])
            ? trim($options['out'])
            : 'storage/kongono-snapshot.json';
        $this->writeSafeCliFile($appRoot, $outOpt, $snapshot->toJson(), allowAbsolute: true);

        return [0, sprintf(
            "Captured Kongono semantic snapshot: %s (%d route(s), %d view(s), %d middleware)\n",
            $outOpt,
            count($snapshot->routes),
            count($snapshot->views),
            count($snapshot->middleware),
        )];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdImpact(string $appRoot, array $options): array
    {
        $app = $this->tryLoadBootstrappedApp($appRoot);
        if (null === $app) {
            return [1, sprintf("No runnable Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $baseOpt = is_string($options['against'] ?? null) && '' !== trim($options['against'])
            ? trim($options['against'])
            : 'storage/kongono-snapshot.json';
        $basePath = str_starts_with($baseOpt, '/') || 1 === preg_match('/\A[a-zA-Z]:[\\\\\/]/', $baseOpt)
            ? $baseOpt
            : rtrim($appRoot, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $baseOpt);

        if (!is_file($basePath)) {
            return [1, sprintf("Baseline snapshot not found at %s. Create one first with `kongo snapshot`.\n", $baseOpt)];
        }
        $decoded = json_decode((string) file_get_contents($basePath), true);
        if (!is_array($decoded)) {
            return [1, sprintf("Invalid snapshot JSON at %s.\n", $baseOpt)];
        }

        /** @var array<string, mixed> $decoded */
        $baseline = AppSnapshot::fromArray($decoded);
        $current = AppSnapshot::capture($appRoot, $app);
        $report = (new ImpactAnalyzer())->diff($baseline, $current);
        $failed = $report['breaking_changes'] > 0 || $report['security_regressions'] > 0;

        if (isset($options['json'])) {
            return [$failed ? 1 : 0, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [
            sprintf('Kongono Semantic Blast-Radius Report — Risk: %s', strtoupper($report['risk_level'])),
            sprintf(
                '  Security regressions: %d | Breaking changes: %d | Routes: +%d/-%d | Views: +%d/-%d',
                $report['security_regressions'],
                $report['breaking_changes'],
                count($report['routes_added']),
                count($report['routes_removed']),
                count($report['views_added']),
                count($report['views_removed']),
            ),
        ];

        if ([] === $report['findings']) {
            $lines[] = '  [OK] Zero security regressions or breaking API/DTO/route changes detected.';
        } else {
            foreach ($report['findings'] as $f) {
                $lines[] = '';
                $lines[] = sprintf('  [%-7s] %s (%s)', strtoupper($f['severity']), $f['code'], $f['where']);
                $lines[] = sprintf('    What:  %s', $f['what']);
                $lines[] = sprintf('    Why:   %s', $f['why']);
                foreach ($f['how_to_fix'] as $idx => $step) {
                    $lines[] = sprintf('    Fix %d: %s', $idx + 1, $step);
                }
            }
        }

        return [$failed ? 1 : 0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdShadowRecord(string $appRoot, array $options): array
    {
        $app = $this->tryLoadBootstrappedApp($appRoot);
        if (null === $app) {
            return [1, sprintf("No runnable Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $corpus = (new ShadowDiffProver())->record($app);
        $outOpt = is_string($options['out'] ?? null) && '' !== trim($options['out'])
            ? trim($options['out'])
            : 'storage/kongono-shadow.json';
        $this->writeSafeCliFile(
            $appRoot,
            $outOpt,
            json_encode($corpus, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            allowAbsolute: true,
        );

        return [0, sprintf("Recorded behavioral shadow baseline: %s (%d route(s))\n", $outOpt, count($corpus['entries']))];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdShadowDiff(string $appRoot, array $options): array
    {
        $app = $this->tryLoadBootstrappedApp($appRoot);
        if (null === $app) {
            return [1, sprintf("No runnable Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
        }

        $baseOpt = is_string($options['against'] ?? null) && '' !== trim($options['against'])
            ? trim($options['against'])
            : 'storage/kongono-shadow.json';
        $basePath = str_starts_with($baseOpt, '/') || 1 === preg_match('/\A[a-zA-Z]:[\\\\\/]/', $baseOpt)
            ? $baseOpt
            : rtrim($appRoot, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $baseOpt);

        if (!is_file($basePath)) {
            return [1, sprintf("Shadow baseline not found at %s. Record one first with `kongo shadow:record`.\n", $baseOpt)];
        }
        $decoded = json_decode((string) file_get_contents($basePath), true);
        if (!is_array($decoded)) {
            return [1, sprintf("Invalid shadow baseline JSON at %s.\n", $baseOpt)];
        }

        /** @var array<string, mixed> $decoded */
        $report = (new ShadowDiffProver())->diff($decoded, $app);
        $failed = $report['regressions'] > 0;

        if (isset($options['json'])) {
            return [$failed ? 1 : 0, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [
            sprintf('Kongono Behavioral Shadow Diff — %s', $report['equivalent'] ? 'EQUIVALENT' : ($failed ? 'REGRESSION' : 'DRIFT WARNING')),
            sprintf('  Routes compared: %d | Regressions: %d | Findings: %d', $report['routes_compared'], $report['regressions'], count($report['findings'])),
        ];
        if ([] === $report['findings']) {
            $lines[] = '  [OK] 100% behavioral equivalence verified across all recorded routes.';
        } else {
            foreach ($report['findings'] as $f) {
                $lines[] = '';
                $lines[] = sprintf('  [%-7s] %s (%s)', strtoupper($f['severity']), $f['code'], $f['where']);
                $lines[] = sprintf('    What:  %s', $f['what']);
                $lines[] = sprintf('    Why:   %s', $f['why']);
            }
        }

        return [$failed ? 1 : 0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdUpgrade(string $appRoot, array $options): array
    {
        $plan = isset($options['plan']);
        $apply = isset($options['apply']);
        if ($plan && $apply) {
            return [1, "Cannot combine --plan and --apply; pass --plan to preview or --apply to write changes.\n"];
        }
        $verify = isset($options['verify']);
        $app = $verify ? $this->tryLoadBootstrappedApp($appRoot) : null;

        $report = (new UpgradeEngine())->run($appRoot, apply: $apply, app: $app, verify: $verify);
        $failed = null !== $report['verification'] && (!$report['verification']['diagnose_passed'] || !$report['verification']['policy_passed']);

        if (isset($options['json'])) {
            return [$failed ? 1 : 0, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [
            sprintf(
                'Kongono Self-Upgrading Codemods (%s) — Scanned %d file(s), %d changed, %d transform(s)',
                strtoupper($report['mode']),
                $report['files_scanned'],
                $report['files_changed'],
                $report['transforms_applied'],
            ),
        ];

        if ([] === $report['changes']) {
            $lines[] = '  [OK] Project is already up to date with all Kongono codemods.';
        } else {
            foreach ($report['changes'] as $change) {
                $lines[] = '';
                $lines[] = sprintf('  %s [%s]', $change['file'], implode(', ', $change['codemods']));
                $lines[] = $change['diff'];
            }
            if (!$apply) {
                $lines[] = '';
                $lines[] = '  Run with `--apply --verify` to apply these syntax-verified transforms and run post-upgrade proofs.';
            }
        }

        if (null !== $report['verification']) {
            $v = $report['verification'];
            $lines[] = '';
            $lines[] = sprintf(
                '  Post-upgrade verification: diagnose=%d/100 (%s) | policy=%d/100 (%s)',
                $v['diagnose_score'],
                $v['diagnose_passed'] ? 'PASS' : 'FAIL',
                $v['policy_score'],
                $v['policy_passed'] ? 'PASS' : 'FAIL',
            );
        }

        return [$failed ? 1 : 0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdWatch(string $appRoot, array $options): array
    {
        $intervalMs = is_string($options['interval'] ?? null) && is_numeric($options['interval'])
            ? max(100, (int) $options['interval'])
            : 500;
        $once = isset($options['once']);
        $runTests = !isset($options['no-tests']);
        $maxCycles = is_string($options['max-cycles'] ?? null) && is_numeric($options['max-cycles'])
            ? max(1, (int) $options['max-cycles'])
            : null;
        $phpunitCmd = $runTests && is_dir($appRoot . DIRECTORY_SEPARATOR . 'tests')
            ? $this->resolveVerifyCommand($appRoot)
            : null;

        return (new WatchRunner())->watch(
            appRoot: $appRoot,
            intervalMs: $intervalMs,
            once: $once,
            runTests: $runTests,
            phpunitCommand: $phpunitCmd,
            maxCycles: $maxCycles,
        );
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdAudit(string $appRoot, array $options): array
    {
        $app = null;
        $bootstrap = $appRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (is_file($bootstrap)) {
            try {
                $loaded = require $bootstrap;
                if ($loaded instanceof App) {
                    $app = $loaded;
                }
            } catch (Throwable) {
                $app = null;
            }
        }

        $attack = isset($options['attack']);
        $report = (new SecurityAuditor())->audit($appRoot, $app, $attack);
        $strict = isset($options['strict']);
        $failed = !$report['passed'] || ($strict && $report['counts']['warning'] > 0);

        if (isset($options['json'])) {
            return [$failed ? 1 : 0, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [
            sprintf('Kongono Security Audit — Score: %d/100 (%s)', $report['score'], $failed ? 'FAILED' : 'PASS'),
            sprintf(
                '  Scanned: %d route(s), %d view(s), %d symfony/* package(s)',
                $report['routes_scanned'],
                $report['views_scanned'],
                $report['symfony_packages'],
            ),
            sprintf(
                '  Findings: %d critical, %d warning, %d info',
                $report['counts']['critical'],
                $report['counts']['warning'],
                $report['counts']['info'],
            ),
        ];

        foreach ($report['findings'] as $f) {
            $lines[] = sprintf('  [%-8s] %s (%s): %s', strtoupper($f['severity']), $f['code'], $f['location'], $f['message']);
        }
        if ([] === $report['findings']) {
            $lines[] = '  [OK      ] Zero security or supply-chain findings.';
        }

        if ([] !== $report['attack_probes']) {
            $lines[] = '  Red-Team Attack Simulation:';
            foreach ($report['attack_probes'] as $probe) {
                $lines[] = sprintf('    [%-4s] %s — %s', $probe['passed'] ? 'PASS' : 'FAIL', $probe['probe'], $probe['detail']);
            }
        }

        return [$failed ? 1 : 0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdDeployInit(string $appRoot, array $options): array
    {
        $domain = is_string($options['domain'] ?? null) && '' !== trim($options['domain'])
            ? trim($options['domain'])
            : 'app.example.com';
        $repo = is_string($options['repo'] ?? null) && '' !== trim($options['repo'])
            ? trim($options['repo'])
            : 'https://github.com/your-org/your-app.git';
        $branch = is_string($options['branch'] ?? null) && '' !== trim($options['branch'])
            ? trim($options['branch'])
            : 'main';
        $ip = is_string($options['ip'] ?? null) && '' !== trim($options['ip'])
            ? trim($options['ip'])
            : '203.0.113.10';
        $threads = is_string($options['threads'] ?? null) && ctype_digit($options['threads'])
            ? max(1, (int) $options['threads'])
            : 4;
        $force = isset($options['force']);

        $files = (new DeployBlueprintGenerator())->generate(
            slug: basename($appRoot),
            domain: $domain,
            gitRepo: $repo,
            gitBranch: $branch,
            serverIp: $ip,
            postgres: isset($options['postgres']) || !isset($options['no-postgres']),
            redis: isset($options['redis']) || !isset($options['no-redis']),
            worker: isset($options['worker']),
            threads: $threads,
        );

        $lines = [sprintf('Kongono Deploy Blueprint (%s):', $domain)];
        foreach ($files as $rel => $content) {
            $dest = $appRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (is_file($dest) && !$force) {
                $lines[] = sprintf('  [SKIP ] %s (already exists; pass --force to overwrite)', $rel);
                continue;
            }
            $this->writeSafeCliFile($appRoot, $rel, $content);
            $lines[] = sprintf('  [WRITE] %s', $rel);
        }

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * Scaffold first-class React 19, Vue 3, or Svelte 5 SPA Bridge adapters (`kongo bridge:init react|vue|svelte`).
     *
     * @param list<string>               $args
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdBridgeInit(string $appRoot, array $args, array $options): array
    {
        $framework = $args[0] ?? (is_string($options['framework'] ?? null) ? $options['framework'] : 'react');
        $force = isset($options['force']);
        $files = (new BridgeScaffolder())->generate($framework);

        $lines = [sprintf('Kongono Bridge (%s):', strtolower(trim($framework)))];
        foreach ($files as $rel => $content) {
            $dest = $appRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (is_file($dest) && !$force) {
                $lines[] = sprintf('  [SKIP ] %s (already exists; pass --force to overwrite)', $rel);
                continue;
            }
            $this->writeSafeCliFile($appRoot, $rel, $content);
            $lines[] = sprintf('  [WRITE] %s', $rel);
        }

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * Generate strict TypeScript prop & BackedEnum declarations from PHP 8.5 DTOs (`kongo bridge:types`).
     *
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdBridgeTypes(string $appRoot, array $options): array
    {
        $srcDir = $appRoot . DIRECTORY_SEPARATOR . 'src';
        /** @var list<class-string> $classes */
        $classes = [];

        if (is_dir($srcDir) && !is_link($srcDir)) {
            $dirIt = new \RecursiveDirectoryIterator($srcDir, \FilesystemIterator::SKIP_DOTS);
            $filter = new \RecursiveCallbackFilterIterator(
                $dirIt,
                static fn(\SplFileInfo $cur): bool => !$cur->isLink(),
            );
            $it = new \RecursiveIteratorIterator($filter);
            foreach ($it as $file) {
                if (!$file instanceof \SplFileInfo || $file->isLink() || !$file->isFile() || 'php' !== $file->getExtension()) {
                    continue;
                }
                $contents = file_get_contents($file->getPathname());
                if (false === $contents) {
                    continue;
                }
                $ns = '';
                if (1 === preg_match('/^\s*namespace\s+([^;]+);/m', $contents, $m)) {
                    $ns = trim($m[1]) . '\\';
                }
                $matchCount = preg_match_all('/^\s*(?:final\s+)?(?:readonly\s+)?(?:class|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $matches);
                if (false !== $matchCount && $matchCount > 0) {
                    foreach ($matches[1] as $short) {
                        $fqcn = $ns . $short;
                        if (str_ends_with($short, 'Controller') || str_ends_with($short, 'Middleware') || str_ends_with($short, 'Service') || str_ends_with($short, 'Store')) {
                            continue;
                        }
                        if (class_exists($fqcn) || enum_exists($fqcn)) {
                            /** @var class-string $fqcn */
                            $classes[] = $fqcn;
                        }
                    }
                }
            }
        }

        sort($classes, SORT_STRING);
        $dts = (new TypeScriptPropsGenerator())->generate($classes);
        $outOpt = is_string($options['out'] ?? null) && '' !== trim($options['out']) ? trim($options['out']) : null;
        if (null !== $outOpt) {
            $context = ProjectContext::discover($appRoot);
            $written = $context->writeFileContained($outOpt, $dts);

            return [0, sprintf("Generated TypeScript Bridge types (%d PHP type(s)) -> %s\n", count($classes), $written)];
        }

        return [0, $dts];
    }

    /**
     * Copy-own zero-npm, strict-CSP accessible Kongono UI components (`kongo ui:add modal drawer ...`).
     *
     * @param list<string>               $args
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdUiAdd(string $appRoot, array $args, array $options): array
    {
        if ([] === $args) {
            return [1, sprintf("Usage: kongo ui:add <%s|all> [--app=path] [--force]\n", implode('|', UiComponentGenerator::AVAILABLE))];
        }

        $force = isset($options['force']);
        $files = (new UiComponentGenerator())->generate($args);
        $lines = ['Kongono UI Primitives (zero-npm, strict-CSP):'];

        foreach ($files as $rel => $content) {
            $dest = $appRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (is_file($dest) && !$force) {
                $lines[] = sprintf('  [SKIP ] %s (already exists; pass --force to overwrite)', $rel);
                continue;
            }
            $this->writeSafeCliFile($appRoot, $rel, $content);
            $lines[] = sprintf('  [WRITE] %s', $rel);
        }

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdMigrateLint(string $appRoot, array $options): array
    {
        $migrationsDir = $appRoot . DIRECTORY_SEPARATOR . 'migrations';
        $report = (new MigrationLinter())->lintDirectory($migrationsDir);
        $strict = isset($options['strict']);
        $failed = !$report['passed'] || ($strict && $report['counts']['warning'] > 0);

        if (isset($options['json'])) {
            return [$failed ? 1 : 0, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [
            sprintf('Kongono Migration Linter (%s):', $failed ? 'FAILED' : 'PASS'),
            sprintf(
                '  Scanned: %d migration file(s) — %d critical, %d warning',
                $report['files_scanned'],
                $report['counts']['critical'],
                $report['counts']['warning'],
            ),
        ];

        foreach ($report['findings'] as $f) {
            $lines[] = sprintf('  [%-8s] %s (%s): %s', strtoupper($f['severity']), $f['code'], $f['file'], $f['message']);
        }
        if ([] === $report['findings']) {
            $lines[] = '  [OK      ] All migrations passed zero-downtime safety checks.';
        }

        return [$failed ? 1 : 0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdEval(string $appRoot, array $options): array
    {
        $runner = new EvalRunner();
        $fileOpt = is_string($options['file'] ?? null) && '' !== trim($options['file']) ? trim($options['file']) : null;
        $suitePath = null !== $fileOpt
            ? (str_starts_with($fileOpt, '/') || (strlen($fileOpt) > 1 && ':' === $fileOpt[1])
                ? $fileOpt
                : $appRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fileOpt))
            : $appRoot . DIRECTORY_SEPARATOR . 'evals' . DIRECTORY_SEPARATOR . 'suite.php';

        $forceRedTeam = isset($options['redteam']);
        $evalCompanions = isset($options['companions']);
        $evalAgentBench = isset($options['agent-bench']);
        $useLive = isset($options['live']);

        if ($evalAgentBench) {
            $ai = $useLive ? $this->tryAiClient() : null;
            $exportOpt = is_string($options['export-jsonl'] ?? null) && '' !== trim($options['export-jsonl']) ? trim($options['export-jsonl']) : null;
            $exportPath = null !== $exportOpt
                ? $this->resolveSafeCliOutputPath($appRoot, $exportOpt, allowAbsolute: true)
                : null;
            $report = (new AgentCodingBenchmark())->run($ai, $exportPath);
            $modeLabel = null !== $ai ? 'Kongono Agent Coding Benchmark (Live LLM + RAG)' : 'Kongono Agent Coding Benchmark (Offline Reference + RAG)';
        } elseif ($evalCompanions) {
            $ai = $useLive ? $this->tryAiClient() : null;
            $context = ProjectContext::discover($appRoot);
            $report = (new CompanionEvalSuite())->run($context, $ai);
            $modeLabel = null !== $ai ? 'AI Companions Benchmark (Live LLM)' : 'AI Companions Benchmark (Offline Invariants)';
        } elseif (!$forceRedTeam && is_file($suitePath)) {
            $loaded = require $suitePath;
            if (!is_array($loaded)) {
                return [1, sprintf("Eval suite %s must return a list of Kongono\\Ai\\Eval\\EvalCase objects.\n", $suitePath)];
            }
            /** @var list<EvalCase> $cases */
            $cases = [];
            foreach ($loaded as $item) {
                if ($item instanceof EvalCase) {
                    $cases[] = $item;
                }
            }
            $ai = $this->tryAiClient();
            if (null === $ai) {
                return [1, "Custom AI eval suite requires an AI provider (OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST), or pass --redteam / --companions for offline evals.\n"];
            }
            $report = $runner->runCases($cases, $ai);
            $modeLabel = 'Custom Eval Suite (' . basename($suitePath) . ')';
        } else {
            $ai = $useLive ? $this->tryAiClient() : null;
            $report = $runner->runRedTeamBattery($ai);
            $modeLabel = null !== $ai ? 'Adversarial Red-Team Battery (Live LLM)' : 'Adversarial Red-Team Battery (Offline PromptGuard)';
        }

        if (isset($options['json'])) {
            return [$report->passed ? 0 : 1, json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"];
        }

        $lines = [
            sprintf('Kongono AI Eval — %s: %d/%d passed (%.1f%%)', $modeLabel, $report->passedCount, $report->total, $report->passRate),
        ];
        foreach ($report->results as $res) {
            $lines[] = sprintf('  [%-4s] %-30s (%.2fms) %s', $res->passed ? 'PASS' : 'FAIL', $res->name, $res->durationMs, $res->outputExcerpt);
            foreach ($res->failures as $failure) {
                $lines[] = '         -> ' . $failure;
            }
        }

        return [$report->passed ? 0 : 1, implode("\n", $lines) . "\n"];
    }

    private function tryAiClient(?string $companion = null): ?AiClient
    {
        if (null !== $this->aiOverride) {
            return $this->aiOverride;
        }

        try {
            return null !== $companion
                ? AiClient::fromEnvForCompanion($companion)
                : AiClient::fromEnv();
        } catch (AiException) {
            return null;
        }
    }

    private function resolveAppRoot(string|true|null $appOption): string
    {
        if (is_string($appOption) && '' !== $appOption) {
            $candidate = str_starts_with($appOption, '/') || (strlen($appOption) > 1 && ':' === $appOption[1])
                ? $appOption
                : $this->workingDirectory . DIRECTORY_SEPARATOR . $appOption;
            $real = realpath($candidate);
            if (false !== $real) {
                return $real;
            }

            return $candidate;
        }

        if (is_file($this->workingDirectory . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php')) {
            return $this->workingDirectory;
        }

        $starter = $this->workingDirectory . DIRECTORY_SEPARATOR . 'examples' . DIRECTORY_SEPARATOR . 'starter-app';
        if (is_dir($starter)) {
            $real = realpath($starter);

            return false !== $real ? $real : $starter;
        }

        return $this->workingDirectory;
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdAuthInit(string $appRoot, array $options): array
    {
        $modeOpt = $options['mode'] ?? 'both';
        $mode = is_string($modeOpt) ? $modeOpt : 'both';
        $force = isset($options['force']);

        $context = ProjectContext::discover($appRoot);
        $result = (new AuthScaffolder())->scaffold($context, $mode, $force);

        $lines = [sprintf('Kongono Auth Starter Kit (mode: %s):', $mode)];
        foreach ($result['created'] as $path) {
            $lines[] = '  [CREATED]     ' . $path;
        }
        foreach ($result['overwritten'] as $path) {
            $lines[] = '  [OVERWRITTEN] ' . $path;
        }
        foreach ($result['skipped'] as $path) {
            $lines[] = '  [SKIPPED]     ' . $path . ' (exists; pass --force to overwrite)';
        }

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdSkillsInstall(string $appRoot, array $options): array
    {
        $targetOpt = $options['target'] ?? 'agents';
        $target = is_string($targetOpt) ? $targetOpt : 'agents';
        $force = isset($options['force']);

        $result = (new SkillsInstaller())->install($appRoot, $target, $force);

        $lines = [sprintf('Kongono AI agent skills (%s):', $target)];
        foreach ($result['installed'] as $path) {
            $lines[] = '  [INSTALLED] ' . $path;
        }
        foreach ($result['updated'] as $path) {
            $lines[] = '  [UPDATED]   ' . $path;
        }
        foreach ($result['unchanged'] as $path) {
            $lines[] = '  [UNCHANGED] ' . $path;
        }
        foreach ($result['skipped'] as $path) {
            $lines[] = '  [SKIPPED]   ' . $path . ' (exists; pass --force to overwrite)';
        }

        return [0, implode("\n", $lines) . "\n"];
    }

    /**
     * @param list<string> $argv
     * @return array{positional: list<string>, options: array<string, string|true>}
     */
    private function parseArgs(array $argv): array
    {
        $positional = [];
        $options = [];
        $count = count($argv);

        for ($i = 0; $i < $count; ++$i) {
            $arg = $argv[$i];
            if (str_starts_with($arg, '--')) {
                $trimmed = substr($arg, 2);
                if (str_contains($trimmed, '=')) {
                    [$k, $v] = explode('=', $trimmed, 2);
                    $options[$k] = $v;
                } elseif (isset($argv[$i + 1]) && !str_starts_with($argv[$i + 1], '--') && in_array($trimmed, ['app', 'host', 'port', 'lesson', 'exercise', 'name', 'queue', 'max-jobs', 'steps', 'eval', 'domain', 'repo', 'branch', 'ip', 'threads', 'lang', 'out', 'file', 'table', 'limit', 'target', 'mode', 'starter', 'max-repairs', 'max-cost', 'base', 'format', 'log', 'interval', 'max-cycles', 'iterations', 'max-ms', 'path', 'against', 'sleep', 'max-sleep', 'max-runtime', 'prune-after', 'framework', 'export-jsonl'], true)) {
                    $options[$trimmed] = $argv[++$i];
                } else {
                    $options[$trimmed] = true;
                }
            } else {
                $positional[] = $arg;
            }
        }

        return ['positional' => $positional, 'options' => $options];
    }

    /**
     * Validate and prepare a safe CLI output file path without writing yet: rejects symlinks on every segment
     * and enforces project-root containment on all relative paths (and on absolute paths unless explicitly opted in).
     */
    private function resolveSafeCliOutputPath(string $appRoot, string $path, bool $allowAbsolute = false): string
    {
        if ('' === trim($path) || str_contains($path, "\0") || 1 === preg_match('/[\x00-\x1f\x7f]/', $path)) {
            throw new CoreException(sprintf('Refusing to write invalid path "%s".', $path));
        }

        $isAbsolute = str_starts_with($path, '/') || 1 === preg_match('/\A[a-zA-Z]:[\\\\\/]/', $path);
        if ($isAbsolute) {
            if (!$allowAbsolute) {
                throw new CoreException(sprintf('Refusing to write absolute path "%s" outside project root.', $path));
            }
            $normAbs = str_replace('\\', '/', $path);
            foreach (explode('/', $normAbs) as $seg) {
                if ('..' === $seg) {
                    throw new CoreException(sprintf('Refusing to write path containing traversal segment "%s".', $path));
                }
            }
            $dest = str_replace('/', DIRECTORY_SEPARATOR, $path);
            // Walk every existing ancestor segment rejecting symbolic links
            $probe = $dest;
            while ($probe !== dirname($probe)) {
                if (is_link($probe)) {
                    throw new CoreException(sprintf('Refusing to write through symbolic link "%s".', $probe));
                }
                $probe = dirname($probe);
            }
            $parent = dirname($dest);
            if (!is_dir($parent) && !@mkdir($parent, 0o775, true) && !is_dir($parent)) {
                throw new CoreException(sprintf('Unable to create directory for "%s".', $path));
            }
            if (is_link($dest)) {
                throw new CoreException(sprintf('Refusing to write through symbolic link "%s".', $path));
            }

            return $dest;
        }

        $realRoot = realpath($appRoot);
        if (false === $realRoot || !is_dir($realRoot)) {
            throw new CoreException(sprintf('Project root directory does not exist: "%s".', $appRoot));
        }
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ('' === $normalized || str_contains($normalized, '..') || str_contains($normalized, ':')) {
            throw new CoreException(sprintf('Refusing to write unsafe relative path "%s".', $path));
        }
        $walk = $realRoot;
        foreach (explode('/', $normalized) as $segment) {
            if (
                '' === $segment
                || '.' === $segment
                || str_ends_with($segment, '.')
                || str_ends_with($segment, ' ')
                || 1 === preg_match('/\A(?:con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\.|\z)/i', $segment)
            ) {
                throw new CoreException(sprintf('Refusing to write invalid path segment in "%s".', $path));
            }
            $walk .= DIRECTORY_SEPARATOR . $segment;
            if (is_link($walk)) {
                throw new CoreException(sprintf('Refusing to write through symbolic link "%s".', $path));
            }
        }

        $dest = $realRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
        $parent = dirname($dest);
        if (!is_dir($parent) && !@mkdir($parent, 0o775, true) && !is_dir($parent)) {
            throw new CoreException(sprintf('Unable to create directory for "%s".', $path));
        }
        $realParent = realpath($parent);
        $prefix = rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (false === $realParent || ($realParent !== $realRoot && !str_starts_with($realParent . DIRECTORY_SEPARATOR, $prefix))) {
            throw new CoreException(sprintf('Target path "%s" escapes project root.', $path));
        }
        if (is_link($dest)) {
            throw new CoreException(sprintf('Refusing to write through symbolic link "%s".', $path));
        }

        return $dest;
    }

    /**
     * Write a CLI output file safely: rejects symlinks on every segment and enforces project-root
     * containment on all relative paths (and on absolute paths unless explicitly opted in for `--out`).
     */
    private function writeSafeCliFile(string $appRoot, string $path, string $content, bool $allowAbsolute = false): string
    {
        $dest = $this->resolveSafeCliOutputPath($appRoot, $path, $allowAbsolute);
        if (false === file_put_contents($dest, $content, LOCK_EX)) {
            throw new CoreException(sprintf('Unable to write file "%s".', $path));
        }

        return $dest;
    }

    /** @param array<string, string|true> $options */
    private function parseMaxCost(array $options): ?float
    {
        $raw = $options['max-cost'] ?? null;
        if (is_string($raw) && is_numeric($raw)) {
            $parsed = (float) $raw;

            return $parsed > 0.0 ? $parsed : null;
        }

        return null;
    }

    /** @return Closure(string): void */
    private function resolveStreamWriter(): Closure
    {
        if (null !== $this->streamWriter) {
            return $this->streamWriter;
        }

        return static function (string $chunk): void {
            fwrite(STDOUT, $chunk);
            fflush(STDOUT);
        };
    }

    private function readInteractiveLine(string $prompt): ?string
    {
        if (null !== $this->promptReader) {
            return ($this->promptReader)($prompt);
        }

        fwrite(STDOUT, $prompt);
        $line = fgets(STDIN);

        return false === $line ? null : $line;
    }

    /** @return list<string> */
    private function resolveVerifyCommand(string $appRoot): array
    {
        $candidates = [
            $appRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit',
            $this->workingDirectory . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit',
        ];
        $phpunit = $candidates[1];
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $phpunit = $candidate;
                break;
            }
        }

        $cmd = [PHP_BINARY, $phpunit];
        foreach (['phpunit.xml', 'phpunit.xml.dist'] as $cfg) {
            $cfgPath = $appRoot . DIRECTORY_SEPARATOR . $cfg;
            if (is_file($cfgPath)) {
                $cmd[] = '--configuration';
                $cmd[] = $cfgPath;

                return $cmd;
            }
        }

        $testsDir = $appRoot . DIRECTORY_SEPARATOR . 'tests';
        if (is_dir($testsDir)) {
            $cmd[] = $testsDir;
        }

        return $cmd;
    }

    /** @return list<array{name: string, command: list<string>}> */
    private function resolveExtraVerifyCommands(string $appRoot): array
    {
        /** @var list<array{name: string, command: list<string>}> $gates */
        $gates = [];

        $phpstanBin = null;
        foreach ([
            $appRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpstan',
            $this->workingDirectory . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpstan',
        ] as $candidate) {
            if (is_file($candidate)) {
                $phpstanBin = $candidate;
                break;
            }
        }
        if (null !== $phpstanBin) {
            foreach (['phpstan.neon', 'phpstan.neon.dist'] as $cfg) {
                $cfgPath = $appRoot . DIRECTORY_SEPARATOR . $cfg;
                if (is_file($cfgPath)) {
                    $gates[] = [
                        'name' => 'phpstan',
                        'command' => [PHP_BINARY, '-d', 'memory_limit=512M', $phpstanBin, 'analyse', '--configuration=' . $cfgPath, '--no-progress'],
                    ];
                    break;
                }
            }
        }

        return $gates;
    }

    private function collectGitDiff(string $projectRoot, string $baseRef): string
    {
        if (1 !== preg_match('/\A[A-Za-z0-9._\/\-~^]+\z/', $baseRef) || str_starts_with($baseRef, '-')) {
            throw new CoreException(sprintf('Unsafe git base ref "%s".', $baseRef));
        }

        // Prefer working tree + staged changes when present; otherwise compare against baseRef...HEAD
        [$wcCode, $wcDiff] = $this->runGitCommand($projectRoot, ['diff', '--no-color']);
        [$stCode, $stDiff] = $this->runGitCommand($projectRoot, ['diff', '--cached', '--no-color']);
        $local = trim((0 === $wcCode ? $wcDiff : '') . "\n" . (0 === $stCode ? $stDiff : ''));
        if ('' !== $local) {
            return $local;
        }

        [$refCode, $refDiff] = $this->runGitCommand($projectRoot, ['diff', '--no-color', $baseRef . '...HEAD']);
        if (0 === $refCode && '' !== trim($refDiff)) {
            return $refDiff;
        }

        return '';
    }

    /**
     * @param list<string> $args
     * @return array{0: int, 1: string}
     */
    private function runGitCommand(string $projectRoot, array $args): array
    {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = @proc_open(['git', ...$args], $descriptors, $pipes, $projectRoot);
        if (!is_resource($proc)) {
            return [1, ''];
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $out = '';
        $open = [1 => $pipes[1], 2 => $pipes[2]];
        while ([] !== $open) {
            $read = array_values($open);
            $write = null;
            $except = null;
            $ready = @stream_select($read, $write, $except, 1);
            if (false === $ready) {
                break;
            }
            foreach ($open as $idx => $stream) {
                $chunk = fread($stream, 65536);
                if (is_string($chunk) && '' !== $chunk && 1 === $idx) {
                    $out .= $chunk;
                }
                if (feof($stream)) {
                    fclose($stream);
                    unset($open[$idx]);
                }
            }
        }
        foreach ($open as $stream) {
            fclose($stream);
        }
        $code = proc_close($proc);

        return [$code, $out];
    }

    /** @return array{0: int, 1: string} */
    private function runOshieRepl(OshieTutor $oshie, ProjectContext $context, ?float $maxCostUsd = null): array
    {
        fwrite(STDOUT, "Oshie Interactive AI Tutor — type a question, /lessons, /graph, /reset, or /exit\n");
        $history = [];
        $writer = $this->resolveStreamWriter();

        while (true) {
            fwrite(STDOUT, "\noshie> ");
            $line = fgets(STDIN);
            if (false === $line) {
                break;
            }
            $input = trim($line);
            if ('' === $input) {
                continue;
            }
            if ('/exit' === $input || '/quit' === $input) {
                break;
            }
            if ('/reset' === $input) {
                $history = [];
                fwrite(STDOUT, "Conversation history cleared.\n");
                continue;
            }
            if ('/lessons' === $input) {
                foreach ($oshie->listLessons() as $id => $meta) {
                    fwrite(STDOUT, sprintf("  %-12s %s\n", $id, $meta['title']));
                }
                continue;
            }
            if ('/graph' === $input) {
                fwrite(STDOUT, (null !== $context->graph ? $context->graph->toJson() : '{"graph":null}') . "\n");
                continue;
            }

            fwrite(STDOUT, "\n");
            $streamed = false;
            $turn = $oshie->converse(
                $input,
                $history,
                $context,
                onDelta: static function (string $delta) use ($writer, &$streamed): void {
                    $streamed = true;
                    $writer($delta);
                },
                maxCostUsd: $maxCostUsd,
            );
            $history = $turn['history'];
            if (!$streamed) {
                fwrite(STDOUT, $turn['reply']);
            }
            fwrite(STDOUT, "\n");
            if (null !== $turn['telemetry']) {
                fwrite(STDOUT, "\n" . $turn['telemetry']->formatFooter() . "\n");
            }
        }

        return [0, "Goodbye from Oshie.\n"];
    }

    private function helpText(): string
    {
        return <<<'TEXT'
Kongono Framework v1.20 — Zero-Legacy, Self-Verifying PHP 8.5+

Usage:
  kongo <command> [arguments] [options]

Day-1 Essentials:
  new <dir> [--name="My App"] [--starter=minimal|api|saas] Scaffold a new Kongono app (minimal, API, or full SaaS)
  serve [--app=path] [--host=127.0.0.1] [--port=8000]   Start local PHP 8.5 dev server (+ Hot Reload & /_kongono)
  prove [--app=path] [--strict] [--json]                Run all 5 Kongono proof engines (Readiness Certificate A+..F)
  diagnose [--app=path] [--fix] [--dry-run] [--json]    Run 17-rule 5W1H self-inspector & contract auto-fixer
  watch [--app=path] [--interval=500] [--no-tests]      Continuously re-run diagnose, policy:check & PHPUnit on save
  migrate [--app=path] [--pretend] [--json]             Run pending migrations (or dry-run preview SQL)
  tinker [--app=path] [--eval="expr"]                   Interactive PHP 8.5 REPL with live $app & $container
  check / test                                          Run full quality suite (CS + PHPStan L9 + PHPUnit + Diagnose)

Self-Verifying Proofs, Incidents & Change-Safety:
  policy:check [--app=path] [--json]                    Verify executable architecture & security invariants (app/invariants.php)
  perf [--app=path] [--iterations=3] [--max-ms=250]     Prove zero persistent-worker leaks, zero SQL N+1 & latency budgets
  prove:isolation [--app=path] [--json]                 Prove multi-tenant & auth isolation against BOLA/IDOR attacks
  audit [--app=path] [--attack] [--json] [--strict]     Run security audit (+ optional --attack red-team probes)
  incident:list [--app=path] [--limit=20] [--json]      List recorded secret-scrubbed KGN-... runtime incidents
  incident:show <KGN-...> [--app=path] [--json]         Inspect 5W1H root cause & trace for a recorded incident
  incident:replay <KGN-...> [--app=path] [--json]       Replay a sanitized production incident offline in-process
  incident:test <KGN-...> [--app=path] [--apply]        Generate a permanent PHPUnit 13 regression test from an incident
  snapshot [--app=path] [--out=file]                    Capture semantic baseline snapshot (routes, security attrs, DTOs, views)
  impact [--app=path] [--against=file] [--json]         Compute semantic PR blast-radius & security regressions
  shadow:record [--app=path] [--out=file]               Record golden behavioral baseline across GET routes
  shadow:diff [--app=path] [--against=file] [--json]    Prove behavioral equivalence after a refactor
  upgrade [--app=path] [--plan|--apply] [--verify]      Run syntax-verified PHP 8.5 & template upgrade codemods

Database & Background Jobs:
  migrate:lint [--app=path] [--json] [--strict]         Lint migrations for zero-downtime DDL & locking hazards
  migrate:status / migrate:rollback [--steps=1]         Inspect or roll back applied database migrations
  migrate:fresh [--app=path] [--seed] [--force]         Drop all tables & re-run all migrations (guarded in prod)
  db:seed [--app=path]                                  Execute SeederInterface files in seeders/
  work [--app=path] [--queue=high,default] [--daemon]   Process background jobs (priority queues & daemon mode)
  queue:failed / queue:retry [<id>|--all] / queue:flush Inspect, replay, or clear dead-lettered background jobs
  schedule:run [--app=path]                             Execute due scheduled cron tasks

Scaffolding, Frontend & SDKs:
  make:resource <Name> [--app=path]                     Generate full CRUD slice (migration, DTOs, repo, controller, test)
  make:controller|dto|migration|seeder|test <Name>      Generate strict PHP 8.5 core application files
  make:job|policy|middleware|factory|rule|mail <Name>   Generate strict PHP 8.5 infrastructure & domain files
  auth:init [--mode=web|api|both] [--force]             Scaffold zero-bloat SessionAuth + PAT Auth Starter Kit
  bridge:init <react|vue|svelte> / bridge:types         Scaffold React 19, Vue 3, or Svelte 5 Bridge SPA & TS types
  ui:add <modal|drawer|dropdown|tabs|toast|datatable|kanban|all> Add zero-npm accessible Kongono UI components
  sdk:generate --lang=ts|swift|kotlin|dart [--out=file] Generate typed Web/iOS/Android/Flutter client SDK
  skills:install [--target=agents|claude|both] [--force] Install 6 bundled Kongono AI agent SKILL.md recipes

Inspection, Editor & Production:
  doctor / env:check / key:generate                     Verify PHP 8.5 + env keys, or generate a 256-bit APP_KEY
  routes [--json] / graph / openapi                     Inspect routes, live ApplicationGraph, or OpenAPI 3.1 spec
  optimize / cache:clear                                Precompile views & AOT route/graph caches (or clear caches)
  deploy:init [--app=path] --domain=app.example.com     Generate zero-downtime FrankenPHP Dockerfile, Caddyfile & VPS scripts
  lsp [--app=path]                                      Run zero-dependency Language Server (LSP 3.17) for VS Code / Cursor
  mcp [--app=path]                                      Run read-only 26-tool Model Context Protocol (MCP) stdio server
  version                                               Show Kongono & PHP version

Optional AI Companions & Evals (per-companion routing: KONGONO_{OSHIE,CHIE,HANTEI}_MODEL, cap: KONGONO_AI_MAX_COST_USD):
  oshie --lessons / --lesson <id> / --exercises / --exercise <id> / --progress
  oshie --interactive / oshie "<question>" [--stream]   Interactive AI tutor (offline v1.20 RAG + live streaming)
  chie "<what to build>" [--interactive] [--apply --verify] [--continue]
  hantei check / debug "<err>" [--stream] / fix [--apply --verify] / review [--base=main --format=sarif]
  hantei watch [--log=file] [--queue=default] / history / queue-failed / generate-tests <file> [--apply]
  eval [--redteam|--companions|--agent-bench] [--live] [--export-jsonl=file] [--json]

TEXT;
    }
}
