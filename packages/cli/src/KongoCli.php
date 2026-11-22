<?php

declare(strict_types=1);

namespace Kongono\Cli;

use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Ai\Eval\EvalCase;
use Kongono\Ai\Eval\EvalRunner;
use Kongono\Cli\Audit\SecurityAuditor;
use Kongono\Cli\Companions\ChieBuilder;
use Kongono\Cli\Companions\DiffRenderer;
use Kongono\Cli\Companions\HanteiDebugger;
use Kongono\Cli\Companions\OshieTutor;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Cli\Database\MigrationLinter;
use Kongono\Cli\Deploy\DeployBlueprintGenerator;
use Kongono\Cli\Mcp\KongonoMcpFactory;
use Kongono\Cli\Sdk\SdkGenerator;
use Kongono\Core\Graph\RouteNode;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Migrator;
use Kongono\Database\SeederInterface;
use Kongono\Http\App;
use Kongono\Http\OpenApi\OpenApiGenerator;
use Kongono\Jobs\Scheduler\Scheduler;
use Kongono\Jobs\Worker;
use Kongono\Security\Encryptor;
use Kongono\View\ViewEngine;
use Throwable;

/** Unified `kongo` CLI entry point (serve, routes, graph, oshie, chie, hantei). */
final readonly class KongoCli
{
    public function __construct(
        private string $workingDirectory,
        private ?AiClient $aiOverride = null,
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
                'version', '-v', '--version' => [0, 'Kongono Framework v1.4.2 (PHP ' . PHP_VERSION . ")\n"],
                'routes' => $this->cmdRoutes($appRoot, $options),
                'graph' => $this->cmdGraph($appRoot),
                'openapi' => $this->cmdOpenApi($appRoot),
                'sdk:generate' => $this->cmdSdkGenerate($appRoot, $options),
                'mcp' => $this->cmdMcp($appRoot),
                'audit' => $this->cmdAudit($appRoot, $options),
                'eval' => $this->cmdEval($appRoot, $options),
                'deploy:init' => $this->cmdDeployInit($appRoot, $options),
                'serve' => $this->cmdServe($appRoot, $options),
                'tinker' => $this->cmdTinker($appRoot, $options),
                'new' => $this->cmdNew($rest, $options),
                'work' => $this->cmdWork($appRoot, $options),
                'schedule:run' => $this->cmdScheduleRun($appRoot),
                'migrate' => $this->cmdMigrate($appRoot),
                'migrate:rollback' => $this->cmdMigrateRollback($appRoot, $options),
                'migrate:status' => $this->cmdMigrateStatus($appRoot),
                'migrate:lint' => $this->cmdMigrateLint($appRoot, $options),
                'db:seed' => $this->cmdDbSeed($appRoot),
                'optimize' => $this->cmdOptimize($appRoot),
                'cache:clear' => $this->cmdCacheClear($appRoot),
                'make:controller' => $this->cmdMake('controller', $appRoot, $rest),
                'make:dto' => $this->cmdMake('dto', $appRoot, $rest),
                'make:migration' => $this->cmdMake('migration', $appRoot, $rest),
                'make:seeder' => $this->cmdMake('seeder', $appRoot, $rest),
                'make:test' => $this->cmdMake('test', $appRoot, $rest),
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
            return [1, sprintf("Error: %s\n", $e->getMessage())];
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
            return [1, "Usage: kongo new <directory> [--name=\"My App\"]\n"];
        }
        $target = str_starts_with($dir, '/') || (strlen($dir) > 1 && ':' === $dir[1])
            ? $dir
            : $this->workingDirectory . DIRECTORY_SEPARATOR . $dir;
        $name = is_string($options['name'] ?? null) ? $options['name'] : ucwords(str_replace(['-', '_'], ' ', basename($dir)));

        $created = (new AppScaffolder())->scaffold($target, $name);
        $lines = [
            sprintf('Created new Kongono v1 application "%s" in %s:', $name, $target),
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
        $report = $worker->run($queue, $maxJobs);

        return [0, sprintf(
            "Worker finished queue \"%s\": processed=%d completed=%d retried=%d failed=%d uncertain=%d stale=%d\n",
            $queue,
            $report->processed,
            $report->completed,
            $report->retried,
            $report->failed,
            $report->uncertain,
            $report->stale,
        )];
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
        $report = $scheduler->runDue();
        $lines = [
            sprintf('Scheduler evaluated %d task(s): ran=%d failed=%d', count($scheduler->tasks()), count($report['ran']), count($report['failed'])),
        ];
        foreach ($report['ran'] as $name) {
            $lines[] = '  [OK] ' . $name;
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
     * @param 'controller'|'dto'|'migration'|'seeder'|'test' $kind
     * @param list<string>                                   $args
     * @return array{0: int, 1: string}
     */
    private function cmdMake(string $kind, string $appRoot, array $args): array
    {
        $name = $args[0] ?? '';
        if ('' === $name) {
            return [1, sprintf("Usage: kongo make:%s <Name> [--app=path]\n", $kind)];
        }

        $context = ProjectContext::discover($appRoot);
        $generator = new CodeGenerator();
        $createdPath = match ($kind) {
            'controller' => $generator->makeController($context, $name),
            'dto' => $generator->makeDto($context, $name),
            'migration' => $generator->makeMigration($context, $name),
            'seeder' => $generator->makeSeeder($context, $name),
            'test' => $generator->makeTest($context, $name),
        };

        return [0, sprintf("Created %s: %s\n", $kind, $createdPath)];
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
        if (!is_dir($cacheDir)) {
            return [0, "Cache directory is already empty.\n"];
        }

        $removed = $this->deleteDirectoryFiles($cacheDir);

        return [0, sprintf("Cleared %d cached file(s) under %s.\n", $removed, $cacheDir)];
    }

    private function deleteDirectoryFiles(string $dir): int
    {
        $count = 0;
        $entries = scandir($dir);
        if (false === $entries) {
            return 0;
        }
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $count += $this->deleteDirectoryFiles($path);
                @rmdir($path);
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

        $seedersDir = $appRoot . DIRECTORY_SEPARATOR . 'seeders';
        $files = is_dir($seedersDir) ? glob($seedersDir . DIRECTORY_SEPARATOR . '*.php') : [];
        if (false === $files || [] === $files) {
            return [0, "No seeder files found.\n"];
        }
        sort($files, SORT_STRING);

        $db = $app->container()->make(ConnectionInterface::class);
        $ran = [];
        foreach ($files as $file) {
            $loaded = require $file;
            if ($loaded instanceof SeederInterface) {
                $loaded->run($db);
                $ran[] = basename($file);
            }
        }

        if ([] === $ran) {
            return [0, "No SeederInterface classes executed.\n"];
        }

        return [0, "Executed seeders:\n" . implode("\n", array_map(static fn(string $f): string => '  + ' . $f, $ran)) . "\n"];
    }

    /** @return array{0: int, 1: string} */
    private function cmdDoctor(string $appRoot): array
    {
        $lines = ['Kongono Environment & Application Doctor:'];
        $allOk = true;

        $phpOk = version_compare(PHP_VERSION, '8.5.0', '>=');
        $lines[] = sprintf('  [%s] PHP version: %s (requires >= 8.5.0)', $phpOk ? 'OK  ' : 'FAIL', PHP_VERSION);
        if (!$phpOk) {
            $allOk = false;
        }

        foreach (['curl', 'json', 'mbstring', 'pdo', 'sodium'] as $ext) {
            $loaded = extension_loaded($ext);
            $lines[] = sprintf('  [%s] ext-%s', $loaded ? 'OK  ' : 'FAIL', $ext);
            if (!$loaded) {
                $allOk = false;
            }
        }

        $ai = $this->tryAiClient();
        $lines[] = null !== $ai
            ? sprintf('  [OK  ] AI companion provider: %s (%s)', $ai->providerName(), $ai->defaultModel())
            : '  [INFO] AI companion provider: offline mode (set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, or OLLAMA_HOST)';

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

    /** @return array{0: int, 1: string} */
    private function cmdMigrate(string $appRoot): array
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

        $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.php');
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
        $ran = (new Migrator($db))->migrate($migrations);
        if ([] === $ran) {
            return [0, "Database is already up to date.\n"];
        }

        return [0, "Applied migrations:\n" . implode("\n", array_map(static fn(string $id): string => '  + ' . $id, $ran)) . "\n"];
    }

    /**
     * @param list<string>               $args
     * @param array<string, string|true> $options
     * @return array{0: int, 1: string}
     */
    private function cmdOshie(string $appRoot, array $args, array $options): array
    {
        $context = ProjectContext::discover($appRoot);
        $oshie = new OshieTutor($this->tryAiClient());

        if (isset($options['interactive'])) {
            return $this->runOshieRepl($oshie, $context);
        }

        if (isset($options['lessons'])) {
            $lines = ['Kongono Oshie — Built-in Lessons:'];
            foreach ($oshie->listLessons() as $id => $meta) {
                $lines[] = sprintf('  %-12s %s — %s', $id, $meta['title'], $meta['summary']);
            }
            $lines[] = "\nRun: kongo oshie --lesson <id>  or  kongo oshie \"<your question>\"";

            return [0, implode("\n", $lines) . "\n"];
        }

        if (isset($options['lesson']) && is_string($options['lesson'])) {
            return [0, $oshie->teachLesson($options['lesson'], $context) . "\n"];
        }

        $question = trim(implode(' ', $args));
        if ('' === $question) {
            return [0, $oshie->teachLesson('routing', $context) . "\n"];
        }

        return [0, $oshie->ask($question, $context) . "\n"];
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
            return [1, "Usage: kongo chie \"<what to build>\" [--app=path] [--apply]\n"];
        }

        $ai = $this->tryAiClient();
        if (null === $ai) {
            return [1, "Chie requires an AI provider. Set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, or OLLAMA_HOST.\n"];
        }

        $context = ProjectContext::discover($appRoot);
        $apply = isset($options['apply']);
        $result = (new ChieBuilder($ai))->build($instruction, $context, $apply);

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
            $lines[] = "\nRe-run with --apply to write these files to disk.";
        }
        if ([] !== $result['next_steps']) {
            $lines[] = "\nNext steps:";
            foreach ($result['next_steps'] as $step) {
                $lines[] = '  - ' . $step;
            }
        }

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
        $hantei = new HanteiDebugger($this->tryAiClient());

        if ('debug' === $sub) {
            $input = trim(implode(' ', $rest));
            if ('' === $input) {
                return [1, "Usage: kongo hantei debug \"<error message or diagnostic>\"\n"];
            }

            return [0, $hantei->diagnose($input, $context) . "\n"];
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
                $out .= "\n--- Hantei AI Diagnosis ---\n" . $report['diagnosis'] . "\n";
            }

            return [$report['exit_code'], $out];
        }

        return [1, "Unknown hantei subcommand. Use: check | debug \"<error>\" | generate-tests <file>\n"];
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
        $useLive = isset($options['live']);

        if (!$forceRedTeam && is_file($suitePath)) {
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
                return [1, "Custom AI eval suite requires an AI provider (OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, or OLLAMA_HOST), or pass --redteam for offline PromptGuard red-teaming.\n"];
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

    private function tryAiClient(): ?AiClient
    {
        if (null !== $this->aiOverride) {
            return $this->aiOverride;
        }

        try {
            return AiClient::fromEnv();
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
                } elseif (isset($argv[$i + 1]) && !str_starts_with($argv[$i + 1], '--') && in_array($trimmed, ['app', 'host', 'port', 'lesson', 'name', 'queue', 'max-jobs', 'steps', 'eval', 'domain', 'repo', 'branch', 'ip', 'threads', 'lang', 'out', 'file'], true)) {
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

    /** @return array{0: int, 1: string} */
    private function runOshieRepl(OshieTutor $oshie, ProjectContext $context): array
    {
        fwrite(STDOUT, "Oshie Interactive AI Tutor — type a question, /lessons, /graph, /reset, or /exit\n");
        $history = [];

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

            $turn = $oshie->converse($input, $history, $context);
            $history = $turn['history'];
            fwrite(STDOUT, "\n" . $turn['reply'] . "\n");
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
  new <dir> [--name="My App"]                           Scaffold a brand-new Kongono v1 project
  serve [--app=path] [--host=127.0.0.1] [--port=8000]   Start local PHP 8.5 dev server
  tinker [--app=path] [--eval="expr"]                   Interactive PHP 8.5 REPL with live $app & $container
  work [--app=path] [--queue=default] [--max-jobs=50]   Process queued background jobs
  schedule:run [--app=path]                             Execute due scheduled cron tasks
  migrate [--app=path]                                  Run pending database migrations
  migrate:rollback [--app=path] [--steps=1]             Roll back applied database migrations
  migrate:status [--app=path]                           Show applied vs pending database migrations
  migrate:lint [--app=path] [--json] [--strict]         Lint migrations for zero-downtime DDL & locking hazards
  db:seed [--app=path]                                  Execute SeederInterface files in seeders/
  optimize [--app=path]                                 Precompile .kgn.html views & cache graph/OpenAPI
  cache:clear [--app=path]                              Clear compiled views & cached app files
  make:controller <Name> [--app=path]                   Generate a PHP 8.5 attribute controller
  make:dto <Name> [--app=path]                          Generate a validated readonly input DTO
  make:migration <Name> [--app=path]                    Generate a Schema::create() migration
  make:seeder <Name> [--app=path]                       Generate a database SeederInterface file
  make:test <Name> [--app=path]                         Generate a PHPUnit 13 TestClient test
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
  mcp [--app=path]                                      Run Model Context Protocol (MCP) JSON-RPC stdio server
  check                                                 Run full CS + PHPStan + PHPUnit suite
  test                                                  Run PHPUnit test suite
  version                                               Show Kongono & PHP version

Built-in AI Companions (OpenAI, Anthropic, Gemini, or local Ollama):
  oshie --lessons                                       List built-in learning lessons
  oshie --lesson <id>                                   Study a lesson (AI-tailored when configured)
  oshie --interactive                                   Start multi-turn interactive AI tutor REPL
  oshie "<question>"                                    Ask Oshie anything about Kongono or your app
  chie "<what to build>" [--apply]                      Generate idiomatic Kongono code (preview by default)
  hantei check                                          Run tests & diagnose failures with AI
  hantei debug "<error or diagnostic>"                  Diagnose an error against your live app
  hantei generate-tests <relative/file.php> [--apply]   Generate PHPUnit 13 tests for a file

TEXT;
    }
}
