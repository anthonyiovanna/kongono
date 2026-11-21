<?php

declare(strict_types=1);

namespace Kongono\Cli;

use Kongono\Ai\AiClient;
use Kongono\Ai\AiException;
use Kongono\Cli\Companions\ChieBuilder;
use Kongono\Cli\Companions\HanteiDebugger;
use Kongono\Cli\Companions\OshieTutor;
use Kongono\Cli\Context\ProjectContext;
use Kongono\Database\ConnectionInterface;
use Kongono\Database\MigrationInterface;
use Kongono\Database\Migrator;
use Kongono\Http\App;
use Kongono\Jobs\Worker;
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
                'version', '-v', '--version' => [0, 'Kongono Framework v1.0.0-alpha (PHP ' . PHP_VERSION . ")\n"],
                'routes' => $this->cmdRoutes($appRoot),
                'graph' => $this->cmdGraph($appRoot),
                'serve' => $this->cmdServe($appRoot, $options),
                'new' => $this->cmdNew($rest, $options),
                'work' => $this->cmdWork($appRoot, $options),
                'migrate' => $this->cmdMigrate($appRoot),
                'make:controller' => $this->cmdMake('controller', $appRoot, $rest),
                'make:dto' => $this->cmdMake('dto', $appRoot, $rest),
                'make:migration' => $this->cmdMake('migration', $appRoot, $rest),
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

    /** @return array{0: int, 1: string} */
    private function cmdRoutes(string $appRoot): array
    {
        $context = ProjectContext::discover($appRoot);
        if (null === $context->graph) {
            return [1, sprintf("No Kongono app found at %s (expected app/bootstrap.php).\n", $appRoot)];
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

    /**
     * @param 'controller'|'dto'|'migration' $kind
     * @param list<string>                   $args
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
        };

        return [0, sprintf("Created %s: %s\n", $kind, $createdPath)];
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
                } elseif (isset($argv[$i + 1]) && !str_starts_with($argv[$i + 1], '--') && in_array($trimmed, ['app', 'host', 'port', 'lesson', 'name', 'queue', 'max-jobs'], true)) {
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

    private function helpText(): string
    {
        return <<<'TEXT'
Kongono Framework v1 — Modern PHP 8.5+ with Built-in AI Companions

Usage:
  kongo <command> [arguments] [options]

Core Commands:
  new <dir> [--name="My App"]                           Scaffold a brand-new Kongono v1 project
  serve [--app=path] [--host=127.0.0.1] [--port=8000]   Start local PHP 8.5 dev server
  work [--app=path] [--queue=default] [--max-jobs=50]   Process queued background jobs
  migrate [--app=path]                                  Run pending database migrations
  make:controller <Name> [--app=path]                   Generate a PHP 8.5 attribute controller
  make:dto <Name> [--app=path]                          Generate a validated readonly input DTO
  make:migration <Name> [--app=path]                    Generate a Schema::create() migration
  routes [--app=path]                                   List all registered routes
  graph [--app=path]                                    Print live ApplicationGraph JSON
  test                                                  Run PHPUnit test suite
  version                                               Show Kongono & PHP version

Built-in AI Companions (OpenAI, Anthropic, Gemini, or local Ollama):
  oshie --lessons                                       List built-in learning lessons
  oshie --lesson <id>                                   Study a lesson (AI-tailored when configured)
  oshie "<question>"                                    Ask Oshie anything about Kongono or your app
  chie "<what to build>" [--apply]                      Generate idiomatic Kongono code (preview by default)
  hantei check                                          Run tests & diagnose failures with AI
  hantei debug "<error or diagnostic>"                  Diagnose an error against your live app
  hantei generate-tests <relative/file.php> [--apply]   Generate PHPUnit 13 tests for a file

TEXT;
    }
}
