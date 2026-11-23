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
use Kongono\Cli\Mcp\KongonoMcpFactory;
use Kongono\Cli\Sdk\SdkGenerator;
use Kongono\Cli\Skills\SkillsInstaller;
use Kongono\Cli\Ui\UiComponentGenerator;
use Kongono\Core\Cache\AtomicCacheInterface;
use Kongono\Core\Config;
use Kongono\Core\CoreException;
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

        $appRoot = $this->resolveAppRoot($options['app'] ?? null);

        try {
            return match ($command) {
                'help', '-h', '--help' => [0, $this->helpText()],
                'version', '-v', '--version' => [0, 'Kongono Framework v1.18.0 (PHP ' . PHP_VERSION . ")\n"],
                'routes' => $this->cmdRoutes($appRoot, $options),
                'graph' => $this->cmdGraph($appRoot),
                'openapi' => $this->cmdOpenApi($appRoot),
                'sdk:generate' => $this->cmdSdkGenerate($appRoot, $options),
                'bridge:init' => $this->cmdBridgeInit($appRoot, $rest, $options),
                'bridge:types' => $this->cmdBridgeTypes($appRoot, $options),
                'ui:add' => $this->cmdUiAdd($appRoot, $rest, $options),
                'auth:init' => $this->cmdAuthInit($appRoot, $options),
                'skills:install' => $this->cmdSkillsInstall($appRoot, $options),
                'mcp' => $this->cmdMcp($appRoot),
                'audit' => $this->cmdAudit($appRoot, $options),
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
                default => [1, sprintf("Unknown command \"%s\". Run `kongo help` for usage.\n", $command)],
            };
        } catch (Throwable $e) {
            return [1, sprintf("Error: %s\n", SecretScrubber::scrub($e->getMessage()))];
        }
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
            $dest = str_starts_with($outPath, '/') || (strlen($outPath) > 1 && ':' === $outPath[1])
                ? $outPath
                : $appRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $outPath);
            $dir = dirname($dest);
            if (!is_dir($dir)) {
                @mkdir($dir, 0o775, true);
            }
            file_put_contents($dest, $code);

            return [0, sprintf("Generated %s SDK (%d bytes) -> %s\n", strtoupper($generator->normalizeLanguage($lang)), strlen($code), $dest)];
        }

        return [0, $code];
    }

    /** @return array{0: int, 1: string} */
    private function cmdMcp(string $appRoot): array
    {
        $server = KongonoMcpFactory::forApp($appRoot);
        $server->serveStdio();

        return [0, ''];
    }

    /**
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdServe(string $appRoot, array $options): array
    {
        $host = is_string($options['host'] ?? null) ? $options['host'] : '127.0.0.1';
        $port = is_string($options['port'] ?? null) ? $options['port'] : '8000';
        $publicDir = $appRoot . DIRECTORY_SEPARATOR . 'public';
        $routerScript = $publicDir . DIRECTORY_SEPARATOR . 'index.php';

        if (!is_dir($publicDir) || !is_file($routerScript)) {
            return [1, sprintf("Cannot serve: %s does not exist.\n", $routerScript)];
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
                'last_error' => $j->lastError,
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
                $job->lastError ?? '-',
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

        $cacheDir = $app->basePath() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache';
        if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0o775, true) && !is_dir($cacheDir)) {
            return [1, sprintf("Unable to create cache directory %s.\n", $cacheDir)];
        }

        $compiledViews = 0;
        if ($app->container()->has(ViewEngine::class)) {
            $compiledViews = $app->container()->make(ViewEngine::class)->warmCache();
        }

        file_put_contents($cacheDir . DIRECTORY_SEPARATOR . 'graph.json', $app->graph()->toJson(pretty: true), LOCK_EX);
        file_put_contents($cacheDir . DIRECTORY_SEPARATOR . 'openapi.json', (new OpenApiGenerator())->generateJson($app), LOCK_EX);
        file_put_contents($cacheDir . DIRECTORY_SEPARATOR . 'routes.compiled.php', $app->router()->exportCompiledPhp(), LOCK_EX);

        return [0, sprintf(
            "Optimized \"%s\":\n  + Precompiled %d template(s)\n  + Compiled var/cache/routes.compiled.php\n  + Cached var/cache/graph.json\n  + Cached var/cache/openapi.json\n",
            $app->graph()->appName,
            $compiledViews,
        )];
    }

    /** @return array{0: int, 1: string} */
    private function cmdCacheClear(string $appRoot): array
    {
        $cacheDir = $appRoot . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache';
        if (is_link($cacheDir)) {
            throw new CoreException('Refusing to clear cache directory: var/cache cannot be a symbolic link.');
        }
        $realCache = realpath($cacheDir);
        if (false === $realCache || !is_dir($realCache)) {
            return [0, "Cache directory is already empty.\n"];
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

            $printed = ob_get_clean();
            $printedStr = false === $printed ? '' : $printed;
            $formatted = null === $value
                ? ($printedStr !== '' ? rtrim($printedStr, "\r\n") : 'null')
                : ($printedStr . $this->formatTinkerValue($value));

            return ['ok' => true, 'output' => $formatted];
        } catch (Throwable $e) {
            ob_end_clean();

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
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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

        if (isset($options['progress'])) {
            $progress = $sessions->oshieProgress();
            $completed = $progress['completed_lessons'];
            $doneMap = array_flip($completed);
            $all = $oshie->listLessons();

            $lines = [
                sprintf('Kongono Oshie — Learning Progress (%d/%d lessons completed):', count($completed), count($all)),
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
                $lines[] = DiffRenderer::render($change['path'], $before, $change['content']);
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
            $diff = DiffRenderer::render($change['path'], $before, $change['content']);

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
                    $lines[] = DiffRenderer::render($change['path'], $before, $change['content']);
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

        return [1, "Unknown hantei subcommand. Use: check | debug \"<error>\" [--stream] | fix [\"<error>\"] [--apply] [--verify] | review [--base=main] [--format=markdown|sarif] | history | queue-failed [<job-id>] | generate-tests <file>\n"];
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
            $dir = dirname($dest);
            if (!is_dir($dir)) {
                @mkdir($dir, 0o775, true);
            }
            file_put_contents($dest, $content);
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
            $dir = dirname($dest);
            if (!is_dir($dir)) {
                @mkdir($dir, 0o775, true);
            }
            file_put_contents($dest, $content);
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

        if (is_dir($srcDir)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcDir));
            foreach ($it as $file) {
                if (!$file instanceof \SplFileInfo || !$file->isFile() || 'php' !== $file->getExtension()) {
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
            $dir = dirname($dest);
            if (!is_dir($dir)) {
                @mkdir($dir, 0o775, true);
            }
            file_put_contents($dest, $content);
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
        $useLive = isset($options['live']);

        if ($evalCompanions) {
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
                } elseif (isset($argv[$i + 1]) && !str_starts_with($argv[$i + 1], '--') && in_array($trimmed, ['app', 'host', 'port', 'lesson', 'name', 'queue', 'max-jobs', 'steps', 'eval', 'domain', 'repo', 'branch', 'ip', 'threads', 'lang', 'out', 'file', 'table', 'limit', 'target', 'mode', 'starter', 'max-repairs', 'max-cost', 'base', 'format'], true)) {
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
        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        return [$code, false === $out ? '' : $out];
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
Kongono Framework v1 — Modern PHP 8.5+ with Built-in AI Companions

Usage:
  kongo <command> [arguments] [options]

Core Commands:
  new <dir> [--name="My App"] [--starter=minimal|api|saas] Scaffold a new Kongono app (minimal, API, or full SaaS)
  serve [--app=path] [--host=127.0.0.1] [--port=8000]   Start local PHP 8.5 dev server
  tinker [--app=path] [--eval="expr"]                   Interactive PHP 8.5 REPL with live $app & $container
  work [--app=path] [--queue=high,default] [--daemon]   Process background jobs (priority queues & daemon mode)
  queue:failed [--queue=default] [--limit=50] [--json]  Inspect failed & dead-lettered background jobs
  queue:retry [<id>|--all] [--queue=default]            Replay one or all failed jobs back to pending
  queue:flush [--queue=default]                         Permanently delete failed & dead-lettered jobs
  schedule:run [--app=path]                             Execute due scheduled cron tasks
  migrate [--app=path] [--pretend] [--json]             Run pending migrations (or dry-run preview SQL)
  migrate:fresh [--app=path] [--seed] [--force]         Drop all tables & re-run all migrations (guarded in prod)
  migrate:rollback [--app=path] [--steps=1]             Roll back applied database migrations
  migrate:status [--app=path]                           Show applied vs pending database migrations
  migrate:lint [--app=path] [--json] [--strict]         Lint migrations for zero-downtime DDL & locking hazards
  db:seed [--app=path]                                  Execute SeederInterface files in seeders/
  optimize [--app=path]                                 Precompile .kgn.html views & cache graph/OpenAPI
  cache:clear [--app=path]                              Clear compiled views & cached app files
  make:controller <Name> [--app=path]                   Generate a PHP 8.5 attribute controller
  make:dto <Name> [--app=path]                          Generate a validated readonly input DTO
  make:migration <Name> [--table=tbl] [--app=path]      Generate a Schema::create() or Schema::table() migration
  make:seeder <Name> [--app=path]                       Generate a database SeederInterface file
  make:test <Name> [--app=path]                         Generate a PHPUnit 13 TestClient test
  make:job <Name> [--app=path]                          Generate a JobHandlerInterface background job
  make:policy <Name> [--app=path]                       Generate a Gate authorization policy
  make:middleware <Name> [--app=path]                   Generate a PSR-15 middleware
  make:factory <Name> [--app=path]                      Generate a typed Factory helper
  make:rule <Name> [--app=path]                         Generate a custom RuleInterface validation attribute
  make:mail <Name> [--app=path]                         Generate a typed Email builder + .kgn.html view
  make:resource <Name> [--app=path]                     Generate full CRUD vertical slice (migration, DTOs, repo, controller, test)
  auth:init [--mode=web|api|both] [--force]             Scaffold zero-bloat SessionAuth + PAT Auth Starter Kit
  key:generate                                          Generate a 256-bit APP_KEY for .env
  doctor [--app=path]                                   Verify PHP 8.5, extensions, AI provider & app health
  audit [--app=path] [--attack] [--json] [--strict]     Run security audit (+ optional --attack red-team probes)
  eval [--app=path] [--file=evals/suite.php] [--redteam] Run AI eval suite or PromptGuard red-team battery
  deploy:init [--app=path] --domain=app.example.com     Generate zero-downtime FrankenPHP Dockerfile, Caddyfile & VPS scripts
  env:check [--app=path]                                Verify required keys from .env.example exist in .env
  routes [--app=path] [--json]                          List all registered routes
  graph [--app=path]                                    Print live ApplicationGraph JSON
  openapi [--app=path]                                  Generate OpenAPI 3.1 JSON spec from routes & DTOs
  sdk:generate --lang=ts|swift|kotlin|dart [--out=file] Generate typed Web/iOS/Android/Flutter client SDK
  bridge:init <react|vue|svelte> [--app=path] [--force] Scaffold first-class React 19, Vue 3, or Svelte 5 Bridge SPA
  bridge:types [--app=path] [--out=file]                Generate strict TypeScript interfaces from PHP 8.5 DTOs & enums
  ui:add <modal|drawer|dropdown|tabs|toast|datatable|kanban|all> Add zero-npm accessible Kongono UI components
  skills:install [--target=agents|claude|both] [--force] Install bundled Kongono AI agent SKILL.md recipes
  mcp [--app=path]                                      Run Model Context Protocol (MCP) JSON-RPC stdio server
  check                                                 Run full CS + PHPStan + PHPUnit suite
  test                                                  Run PHPUnit test suite
  version                                               Show Kongono & PHP version

Built-in AI Companions (per-companion routing: KONGONO_{OSHIE,CHIE,HANTEI}_MODEL, auto-failover, cap: KONGONO_AI_MAX_COST_USD):
  oshie --lessons                                       List built-in learning lessons
  oshie --lesson <id>                                   Study a lesson & record progress (AI-tailored when configured)
  oshie --progress                                      Track completed lessons & get tailored project exercises
  oshie --interactive                                   Start multi-turn streaming interactive AI tutor REPL
  oshie "<question>" [--stream] [--max-cost=0.10]       Ask Oshie anything about Kongono or your app
  chie "<what to build>" [--apply] [--verify]           Generate Kongono code (multi-gate PHPStan+tests+lint+audit auto-repair)
  chie "<follow-up>" --continue [--apply]               Iterate on your previous Chie build session
  chie "<what to build>" --interactive                  Approve or reject each proposed file diff interactively
  hantei check                                          Run tests & diagnose failures with AI
  hantei debug "<error or diagnostic>" [--stream]       Diagnose an error using live code & schema tools
  hantei fix ["<error>"] [--apply] [--verify]           Diagnose a failure, hand off minimal patch to Chie, and verify
  hantei review [--base=main] [--format=markdown|sarif] Review git diff for Kongono invariants (CI/SARIF ready)
  hantei history [--limit=10]                           List recent Hantei diagnoses for this project
  hantei queue-failed [<job-id>] [--queue=default]      Diagnose a failed/dead-lettered background job with AI
  hantei generate-tests <relative/file.php> [--apply]   Generate PHPUnit 13 tests for a file
  eval --companions [--live] [--json]                   Run Oshie/Chie/Hantei zero-hallucination & safety benchmarks

TEXT;
    }
}
