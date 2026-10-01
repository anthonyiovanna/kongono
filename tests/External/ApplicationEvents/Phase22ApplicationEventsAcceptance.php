<?php

declare(strict_types=1);

namespace Kongono\Tests\External\ApplicationEvents;

use Closure;
use Error;
use EventMinimalApp\EventComposition;
use EventMinimalApp\InvoicePaid;
use Kongono\EventContract\ApplicationEvent;
use Kongono\EventContract\EventHandler;
use Kongono\EventContract\Exception\InvalidEventContractException;
use Kongono\EventContract\Registration\EventRegistration;
use Kongono\EventContract\Registration\EventRegistrationSet;
use Kongono\EventRuntime\Dispatch\SynchronousEventDispatcher;
use Kongono\EventRuntime\Exception\ReentrantDispatchException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use RuntimeException;
use SplFileInfo;
use Throwable;
use TypeError;

/** Root-owned acceptance over accepted APIs and a separate own-autoload copy. */
final class Phase22ApplicationEventsAcceptance
{
    private const string FAILURE = "phase22_acceptance_failed\n";
    private const string COMPOSER_SHA256 = '5EE7125F8A30A34D246CEFDC0BC85B8A783B28F2AEC968994118512350D28027';
    private const string PHP_SHA256 = 'AB48ED7BC789D966CBC2AB85C54669FFCD5DD66259AFE94F5B07DB0C75D68A28';
    /** @var list<string> */
    private const array GROUPS = [
        'loading-registration',
        'exact-delivery',
        'idle-native',
        'authorization',
        'throwable-progress',
        'reentry-isolation-repeat',
        'safe-presentation',
        'copied-consumer',
    ];
    /** @var list<string> */
    private const array TEMPLATE_PATHS = [
        'README.md',
        'bin/demo.php',
        'composer.json',
        'composer.lock',
        'kongono.skeleton.json',
        'src/EventComposition.php',
        'src/InvoicePaid.php',
        'tests/EventMinimalApplicationTest.php',
    ];

    private ?TemporaryEventWorkspace $workspace = null;
    private string $composerPhar = '';
    private int $childCount = 0;
    /** @var list<string> */
    private array $completed = [];

    public function __construct(private readonly string $repositoryRoot) {}

    /** @param list<string> $arguments
     * @param resource $stdout
     * @param resource $stderr
     */
    public function run(array $arguments, $stdout, $stderr): int
    {
        if ([] !== $arguments) {
            fwrite($stderr, self::FAILURE);

            return 1;
        }

        try {
            $this->preflightTools();
            $root = getenv('KONGONO_PHASE22F_EVIDENCE_ROOT');
            if (!is_string($root) || '' === $root) {
                throw new RuntimeException('phase22_environment_failed');
            }
            $this->workspace = new TemporaryEventWorkspace($this->repositoryRoot, $root);
            $before = $this->repositoryFingerprint();

            $this->loadingRegistration();
            $this->record('loading-registration');
            $this->exactDelivery();
            $this->record('exact-delivery');
            $this->idleNative();
            $this->record('idle-native');
            $this->authorization();
            $this->record('authorization');
            $this->throwableProgress();
            $this->record('throwable-progress');
            $this->reentryIsolationRepeat();
            $this->record('reentry-isolation-repeat');
            $this->safePresentation();
            $this->record('safe-presentation');
            $this->copiedConsumer();
            $this->record('copied-consumer');

            $this->check($before === $this->repositoryFingerprint());
            $this->check(self::GROUPS === $this->completed);
            $result = [
                'schema' => 'kongono.phase-22-application-events-acceptance',
                'version' => 1,
                'profile' => 'application-events-local/1',
                'checks' => $this->completed,
                'count' => count($this->completed),
            ];
            fwrite($stdout, json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");

            return 0;
        } catch (Throwable $failure) {
            if (null !== $this->workspace) {
                $details = json_encode([
                    'class' => $failure::class,
                    'message' => $failure->getMessage(),
                    'completed' => $this->completed,
                    'children' => $this->childCount,
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                $this->workspace->write('evidence/failure.json', $details);
            }
            fwrite($stderr, self::FAILURE);

            return 1;
        }
    }

    private function preflightTools(): void
    {
        $candidate = getenv('KONGONO_PHASE22F_COMPOSER_PHAR');
        if (!is_string($candidate)
            || !str_starts_with(str_replace('\\', '/', $candidate), 'C:/')
            || !TemporaryEventWorkspace::ordinary($candidate)
            || strtoupper((string) hash_file('sha256', $candidate)) !== self::COMPOSER_SHA256
            || !TemporaryEventWorkspace::ordinary(PHP_BINARY)
            || strtoupper((string) hash_file('sha256', PHP_BINARY)) !== self::PHP_SHA256
            || '1' !== getenv('COMPOSER_DISABLE_NETWORK')
            || false !== php_ini_scanned_files()
            || '512M' !== ini_get('memory_limit')
        ) {
            throw new RuntimeException('phase22_environment_failed');
        }
        $this->composerPhar = $candidate;
    }

    private function loadingRegistration(): void
    {
        $this->check(!class_exists(InvoicePaid::class, false));
        $this->check(class_exists(InvoicePaid::class));
        $calls = 0;
        $handler = self::handler(static function (ApplicationEvent $event) use (&$calls): void {
            ++$calls;
        });
        $registration = new EventRegistration(InvoicePaid::class, $handler);
        $set = new EventRegistrationSet([$registration]);
        $this->check(1 === $set->count() && 0 === $calls);

        try {
            new EventRegistrationSet([$registration, $registration]);
            throw new RuntimeException('phase22_case_failed');
        } catch (InvalidEventContractException $failure) {
            $this->check('KGN-EVENT-CONTRACT-2213' === $failure->diagnosticId());
        }
        try {
            new EventRegistration('PRIVATE EVENT/TYPE', $handler);
            throw new RuntimeException('phase22_case_failed');
        } catch (InvalidEventContractException $failure) {
            $this->check('KGN-EVENT-CONTRACT-2211' === $failure->diagnosticId());
            $this->check(!str_contains((string) $failure, 'PRIVATE EVENT/TYPE'));
        }
        $this->check(0 === $calls);
    }

    private function exactDelivery(): void
    {
        $order = [];
        $first = self::handler(static function (ApplicationEvent $event) use (&$order): void {
            $order[] = ['first', $event];
        });
        $second = self::handler(static function (ApplicationEvent $event) use (&$order): void {
            $order[] = ['second', $event];
        });
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([
            new EventRegistration(AcceptanceBaseEvent::class, $first),
            new EventRegistration(InvoicePaid::class, $first),
            new EventRegistration(InvoicePaid::class, $second),
        ]));
        $dispatcher->dispatch(new AcceptanceDerivedEvent());
        $this->check([] === $order);
        $event = new InvoicePaid('F-ORDER');
        $dispatcher->dispatch($event);
        $this->check([['first', $event], ['second', $event]] === $order);
        $this->check($order[0][1] === $event && $order[1][1] === $event);
    }

    private function idleNative(): void
    {
        $calls = 0;
        $handler = self::handler(static function (ApplicationEvent $event) use (&$calls): void {
            ++$calls;
        });
        (new SynchronousEventDispatcher(new EventRegistrationSet([])))->dispatch(new InvoicePaid('F-IDLE'));
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([
            new EventRegistration(InvoicePaid::class, $handler),
        ]));
        $dispatcher->dispatch(new AcceptanceBaseEvent());
        try {
            (new ReflectionMethod($dispatcher, 'dispatch'))->invoke($dispatcher, new \stdClass());
            throw new RuntimeException('phase22_case_failed');
        } catch (TypeError) {
            $this->check(0 === $calls);
        }
    }

    private function authorization(): void
    {
        foreach (['receipt', 'ledger'] as $denied) {
            $policy = [];
            $effects = [];
            $dispatcher = EventComposition::create(
                static function (string $action, InvoicePaid $event) use ($denied, &$policy): bool {
                    $policy[] = $action;

                    return $action !== $denied;
                },
                static function (InvoicePaid $event) use (&$effects): void {
                    $effects[] = 'receipt';
                },
                static function (InvoicePaid $event) use (&$effects): void {
                    $effects[] = 'ledger';
                },
            );
            try {
                $dispatcher->dispatch(new InvoicePaid('F-AUTH'));
                throw new RuntimeException('phase22_case_failed');
            } catch (RuntimeException $failure) {
                $this->check('Application effect was denied.' === $failure->getMessage());
            }
            $this->check(('receipt' === $denied ? ['receipt'] : ['receipt', 'ledger']) === $policy);
            $this->check(('receipt' === $denied ? [] : ['receipt']) === $effects);
        }
    }

    private function throwableProgress(): void
    {
        foreach ([new RuntimeException('PRIVATE-EXCEPTION'), new Error('PRIVATE-ERROR')] as $original) {
            $effects = [];
            $dispatcher = EventComposition::create(
                static fn(string $action, InvoicePaid $event): bool => true,
                static function (InvoicePaid $event) use (&$effects): void {
                    $effects[] = 'receipt';
                },
                static function (InvoicePaid $event) use ($original): never {
                    throw $original;
                },
            );
            try {
                $dispatcher->dispatch(new InvoicePaid('F-FAIL'));
                throw new RuntimeException('phase22_case_failed');
            } catch (Throwable $caught) {
                $this->check($caught === $original);
            }
            $this->check(['receipt'] === $effects);
        }
    }

    private function reentryIsolationRepeat(): void
    {
        $state = new class {
            public bool $nested = true;
            public bool $catch = true;
            public ?SynchronousEventDispatcher $dispatcher = null;
            public int $rejections = 0;
        };
        $effects = [];
        $handler = self::handler(static function (ApplicationEvent $event) use ($state, &$effects): void {
            $effects[] = 'first';
            if ($state->nested) {
                $dispatcher = $state->dispatcher;
                if (null === $dispatcher) {
                    throw new RuntimeException('phase22_case_failed');
                }
                if ($state->catch) {
                    try {
                        $dispatcher->dispatch($event);
                    } catch (ReentrantDispatchException $failure) {
                        ++$state->rejections;
                    }
                } else {
                    $dispatcher->dispatch($event);
                }
            }
        });
        $last = self::handler(static function (ApplicationEvent $event) use (&$effects): void {
            $effects[] = 'last';
        });
        $dispatcher = new SynchronousEventDispatcher(new EventRegistrationSet([
            new EventRegistration(InvoicePaid::class, $handler),
            new EventRegistration(InvoicePaid::class, $last),
        ]));
        $state->dispatcher = $dispatcher;
        $dispatcher->dispatch(new InvoicePaid('F-CAUGHT'));
        $this->check(1 === $state->rejections && ['first', 'last'] === $effects);
        $state->catch = false;
        try {
            $dispatcher->dispatch(new InvoicePaid('F-ESCAPE'));
            throw new RuntimeException('phase22_case_failed');
        } catch (ReentrantDispatchException $failure) {
            $this->check('KGN-EVENT-RUNTIME-2221' === $failure->diagnosticId());
        }
        $state->nested = false;
        $event = new InvoicePaid('F-REPEAT');
        $dispatcher->dispatch($event);
        $dispatcher->dispatch($event);
        $this->check(['first', 'last', 'first', 'first', 'last', 'first', 'last'] === $effects);

        $otherEffects = [];
        $other = new SynchronousEventDispatcher(new EventRegistrationSet([
            new EventRegistration(InvoicePaid::class, self::handler(static function (ApplicationEvent $event) use (&$otherEffects): void {
                $otherEffects[] = $event;
            })),
        ]));
        $other->dispatch($event);
        $this->check([$event] === $otherEffects);
    }

    private function safePresentation(): void
    {
        $workspace = $this->owned();
        $autoload = var_export($this->repositoryRoot . '/vendor/autoload.php', true);
        $script = <<<'PHP'
<?php
declare(strict_types=1);
require %AUTOLOAD%;
$secret = 'PRIVATE-PHASE22F-DIAGNOSTIC/INPUT';
$failure = \Kongono\EventContract\Exception\InvalidEventContractException::invalidEventType($secret);
$safe = $failure->getMessage() . (string) $failure . $failure->diagnosticTitle() . $failure->diagnosticRemediation();
foreach ($failure->diagnosticContext()->fields() as $field) {
    $safe .= $field->value();
}
$nested = \Kongono\EventRuntime\Exception\ReentrantDispatchException::active();
$safe .= (string) $nested . $nested->diagnosticTitle() . $nested->diagnosticRemediation();
if (str_contains($safe, $secret) || !str_contains($safe, 'KGN-EVENT-CONTRACT-2211') || !str_contains($safe, 'KGN-EVENT-RUNTIME-2221') || !in_array(ini_get('zend.exception_ignore_args'), ['0', '1'], true)) {
    fwrite(STDERR, "event_diagnostic_failed\n");
    exit(1);
}
fwrite(STDOUT, "event_diagnostic_ok\n");
PHP;
        $path = $workspace->write('work/diagnostic.php', str_replace('%AUTOLOAD%', $autoload, $script) . "\n");
        foreach (['0', '1'] as $mode) {
            $result = $this->capture('diagnostic-' . $mode, [PHP_BINARY, '-d', 'memory_limit=512M', '-d', 'zend.exception_ignore_args=' . $mode, $path], $workspace->root(), 60, 8 * 1024 * 1024);
            $this->check(0 === $result->exitCode && "event_diagnostic_ok\n" === $result->stdout() && '' === $result->stderr());
        }
    }

    private function copiedConsumer(): void
    {
        $workspace = $this->owned();
        $source = $this->repositoryRoot . '/skeletons/event-minimal';
        $manifest = json_decode((string) file_get_contents($source . '/kongono.skeleton.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->check(is_array($manifest) && ($manifest['template_paths'] ?? null) === self::TEMPLATE_PATHS);
        $consumer = $workspace->path('consumer');
        $this->check(!file_exists($consumer) && !is_link($consumer));
        $workspace->directory('consumer');
        foreach (self::TEMPLATE_PATHS as $relative) {
            $origin = $source . '/' . $relative;
            $this->check(TemporaryEventWorkspace::ordinary($origin));
            $bytes = file_get_contents($origin);
            if (false === $bytes) {
                throw new RuntimeException('phase22_case_failed');
            }
            $copy = $workspace->write('consumer/' . $relative, $bytes);
            $this->check(hash_file('sha256', $origin) === hash_file('sha256', $copy));
        }
        $workspace->write('evidence/copied-original-composer.json', (string) file_get_contents($consumer . '/composer.json'));
        $workspace->write('evidence/copied-original-composer.lock', (string) file_get_contents($consumer . '/composer.lock'));
        $this->check(file_exists($consumer));

        $manifestPath = $consumer . '/composer.json';
        $manifestBytes = file_get_contents($manifestPath);
        if (false === $manifestBytes) {
            throw new RuntimeException('phase22_case_failed');
        }
        $decoded = json_decode($manifestBytes, flags: JSON_THROW_ON_ERROR);
        $original = json_decode($manifestBytes, flags: JSON_THROW_ON_ERROR);
        if (!$decoded instanceof \stdClass || !$original instanceof \stdClass || !is_array($decoded->repositories ?? null)) {
            throw new RuntimeException('phase22_case_failed');
        }
        $repositories = [['packagist.org' => false]];
        foreach (['api-metadata', 'diagnostics', 'event-contract', 'event-runtime'] as $name) {
            $package = $this->repositoryRoot . '/packages/' . $name;
            $this->check(is_dir($package) && !is_link($package));
            $repositories[] = ['type' => 'path', 'url' => str_replace('\\', '/', $package), 'options' => ['symlink' => false]];
        }
        $decoded->repositories = $repositories;
        $replacement = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $this->check(strlen($replacement) === file_put_contents($manifestPath, $replacement, LOCK_EX));
        $decodedAgain = json_decode((string) file_get_contents($manifestPath), flags: JSON_THROW_ON_ERROR);
        if (!$decodedAgain instanceof \stdClass
            || json_encode($repositories, JSON_THROW_ON_ERROR) !== json_encode($decodedAgain->repositories ?? null, JSON_THROW_ON_ERROR)) {
            throw new RuntimeException('phase22_case_failed');
        }
        unset($original->repositories, $decodedAgain->repositories);
        $this->check(json_encode($original, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)
            === json_encode($decodedAgain, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $autoload = $original->autoload ?? null;
        $autoloadDev = $original->{'autoload-dev'} ?? null;
        if (!$autoload instanceof \stdClass || !$autoloadDev instanceof \stdClass) {
            throw new RuntimeException('phase22_case_failed');
        }
        $psr4 = $autoload->{'psr-4'} ?? null;
        $psr4Dev = $autoloadDev->{'psr-4'} ?? null;
        $this->check($psr4 instanceof \stdClass && $psr4Dev instanceof \stdClass
            && isset($psr4->{'EventMinimalApp\\'}, $psr4Dev->{'EventMinimalApp\\Tests\\'}));

        $base = [PHP_BINARY, '-d', 'memory_limit=512M', $this->composerPhar, '--working-dir=' . $consumer];
        $update = $this->capture('consumer-update', [...$base, 'update', '--no-install', '--no-interaction', '--no-audit', '--no-scripts', '--no-plugins'], $consumer, 600, 8 * 1024 * 1024);
        $this->check(0 === $update->exitCode);
        $install = $this->capture('consumer-install', [...$base, 'install', '--no-dev', '--no-interaction', '--no-scripts', '--no-plugins'], $consumer, 600, 8 * 1024 * 1024);
        $this->check(0 === $install->exitCode);
        $validate = $this->capture('consumer-validate', [...$base, 'validate', '--no-check-publish'], $consumer, 600, 8 * 1024 * 1024);
        $this->check(0 === $validate->exitCode);
        $this->inspectConsumer($consumer);

        $warningArgs = [PHP_BINARY, '-d', 'memory_limit=512M', '-d', 'display_errors=stderr', '-d', 'log_errors=0', '-d', 'error_reporting=-1'];
        $demo = $this->capture('consumer-demo', [...$warningArgs, $consumer . '/bin/demo.php'], $consumer, 60, 8 * 1024 * 1024);
        $this->check(0 === $demo->exitCode && "event_demo_ok\n" === $demo->stdout() && '' === $demo->stderr());

        $workspace->write('missing/bin/demo.php', (string) file_get_contents($consumer . '/bin/demo.php'));
        $missing = $this->capture('missing-autoload', [...$warningArgs, $workspace->path('missing/bin/demo.php')], $workspace->path('missing'), 60, 1024 * 1024);
        $this->check(1 === $missing->exitCode && '' === $missing->stdout() && "event_demo_failed\n" === $missing->stderr());
        $demoSource = (string) file_get_contents($consumer . '/bin/demo.php');
        $this->check(str_contains($demoSource, 'set_error_handler(')
            && str_contains($demoSource, 'require dirname(__DIR__)')
            && str_contains($demoSource, 'finally {')
            && str_contains($demoSource, 'restore_error_handler();'));
        $demoPath = var_export($consumer . '/bin/demo.php', true);
        $sentinel = <<<'PHP'
<?php
declare(strict_types=1);
$sentinel = static function (): void {};
set_error_handler($sentinel, E_WARNING);
try {
    require %DEMO%;
    $prior = set_error_handler(static function (): void {});
    restore_error_handler();
    if ($prior !== $sentinel) {
        throw new RuntimeException('caller handler changed');
    }
    fwrite(STDOUT, "handler_restored_ok\n");
} catch (Throwable) {
    fwrite(STDERR, "handler_restore_failed\n");
    exit(1);
} finally {
    restore_error_handler();
}
PHP;
        $sentinelPath = $workspace->write('work/sentinel.php', str_replace('%DEMO%', $demoPath, $sentinel) . "\n");
        $restored = $this->capture('caller-handler', [...$warningArgs, $sentinelPath], $consumer, 60, 8 * 1024 * 1024);
        $this->check(0 === $restored->exitCode && "event_demo_ok\nhandler_restored_ok\n" === $restored->stdout() && '' === $restored->stderr());
    }

    private function inspectConsumer(string $consumer): void
    {
        $lock = json_decode((string) file_get_contents($consumer . '/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($lock) || !is_array($lock['packages'] ?? null) || [] !== ($lock['packages-dev'] ?? null)) {
            throw new RuntimeException('phase22_case_failed');
        }
        $expected = ['kongono/api-metadata', 'kongono/diagnostics', 'kongono/event-contract', 'kongono/event-runtime'];
        $found = [];
        foreach ($lock['packages'] as $package) {
            if (!is_array($package) || !is_string($package['name'] ?? null)) {
                throw new RuntimeException('phase22_case_failed');
            }
            $packageName = $package['name'];
            $dist = $package['dist'] ?? null;
            if (!is_array($dist) || !is_string($dist['url'] ?? null)) {
                throw new RuntimeException('phase22_case_failed');
            }
            $found[] = $packageName;
            $name = substr($packageName, strlen('kongono/'));
            $this->check(in_array($packageName, $expected, true)
                && str_replace('\\', '/', $dist['url']) === str_replace('\\', '/', $this->repositoryRoot . '/packages/' . $name));
        }
        sort($found, SORT_STRING);
        sort($expected, SORT_STRING);
        $this->check($expected === $found);

        $installed = 0;
        foreach (['api-metadata', 'diagnostics', 'event-contract', 'event-runtime'] as $name) {
            $base = $consumer . '/vendor/kongono/' . $name;
            $this->check(is_dir($base) && !is_link($base));
            $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($entries as $entry) {
                if (!$entry instanceof SplFileInfo) {
                    throw new RuntimeException('phase22_case_failed');
                }
                $this->check(!$entry->isLink());
                if ($entry->isDir()) {
                    continue;
                }
                $this->check($entry->isFile() && TemporaryEventWorkspace::ordinary($entry->getPathname()));
                $relative = substr(str_replace('\\', '/', $entry->getPathname()), strlen(str_replace('\\', '/', $base)) + 1);
                $source = $this->repositoryRoot . '/packages/' . $name . '/' . $relative;
                $this->check(TemporaryEventWorkspace::ordinary($source) && hash_file('sha256', $source) === hash_file('sha256', $entry->getPathname()));
                ++$installed;
            }
        }
        $this->check($installed > 0 && $installed <= 128);
        $this->owned()->write('evidence/consumer-installed.json', json_encode(['packages' => $found, 'files' => $installed], JSON_THROW_ON_ERROR));
    }

    /** @param list<string> $arguments */
    private function capture(string $label, array $arguments, string $cwd, int $timeoutSeconds, int $limit): ProcessResult
    {
        if (1 !== preg_match('/\A[a-z0-9-]+\z/D', $label)
            || $this->childCount >= 12
            || $timeoutSeconds < 1
            || $timeoutSeconds > 600
            || !is_dir($cwd)
            || !TemporaryEventWorkspace::within($cwd, $this->owned()->root())
        ) {
            throw new RuntimeException('phase22_process_failed');
        }
        ++$this->childCount;
        $workspace = $this->owned();
        $stdoutPath = $workspace->emptyFile('capture/' . $label . '/stdout.bin');
        $stderrPath = $workspace->emptyFile('capture/' . $label . '/stderr.bin');
        $workspace->write('capture/' . $label . '/argv.json', json_encode(['argv' => $arguments, 'cwd' => $cwd], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        $environment = [];
        foreach (['PATH', 'PHPRC', 'PHP_INI_SCAN_DIR', 'SystemRoot', 'WINDIR'] as $name) {
            $value = getenv($name);
            if (is_string($value)) {
                $environment[$name] = $value;
            }
        }
        $environment['COMPOSER_DISABLE_NETWORK'] = '1';
        $environment['COMPOSER_CACHE_DIR'] = $workspace->path('composer-cache');
        $environment['COMPOSER_HOME'] = $workspace->path('composer-home');
        $environment['TEMP'] = $workspace->path('work');
        $environment['TMP'] = $environment['TEMP'];
        $pipes = [];
        $process = proc_open($arguments, [0 => ['pipe', 'r'], 1 => ['file', $stdoutPath, 'wb'], 2 => ['file', $stderrPath, 'wb']], $pipes, $cwd, $environment, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new RuntimeException('phase22_process_failed');
        }
        fclose($pipes[0]);
        $started = hrtime(true);
        $pid = 0;
        $exit = -1;
        $retired = false;
        $timedOut = false;
        try {
            do {
                $status = proc_get_status($process);
                $this->check($status['pid'] > 0);
                $this->check(0 === $pid || $pid === $status['pid']);
                $pid = $status['pid'];
                if ($status['exitcode'] >= 0) {
                    $exit = $status['exitcode'];
                }
                clearstatcache(true, $stdoutPath);
                clearstatcache(true, $stderrPath);
                $outSize = filesize($stdoutPath);
                $errSize = filesize($stderrPath);
                if (false === $outSize || false === $errSize || $outSize > $limit || $errSize > $limit) {
                    throw new RuntimeException('phase22_process_overflow');
                }
                if (!$status['running']) {
                    $retired = true;
                    break;
                }
                if (hrtime(true) - $started > $timeoutSeconds * 1_000_000_000) {
                    $timedOut = true;
                    throw new RuntimeException('phase22_process_timeout');
                }
                usleep(10_000);
            } while (true);
        } catch (Throwable $failure) {
            $this->retire($process, $pid);
            proc_close($process);
            throw $failure;
        }
        $closed = proc_close($process);
        if ($exit < 0) {
            $exit = $closed;
        }
        $stdoutBytes = (int) filesize($stdoutPath);
        $stderrBytes = (int) filesize($stderrPath);
        $result = new ProcessResult($exit, $stdoutPath, $stderrPath, $stdoutBytes, $stderrBytes, (string) hash_file('sha256', $stdoutPath), (string) hash_file('sha256', $stderrPath), $timedOut, $retired, $pid, hrtime(true) - $started);
        $workspace->write('capture/' . $label . '/result.json', json_encode([
            'exit' => $exit,
            'pid' => $pid,
            'stdout_bytes' => $stdoutBytes,
            'stderr_bytes' => $stderrBytes,
            'stdout_sha256' => $result->stdoutSha256,
            'stderr_sha256' => $result->stderrSha256,
            'retired' => $retired,
            'timed_out' => $timedOut,
            'elapsed_ns' => $result->elapsedNanoseconds,
        ], JSON_THROW_ON_ERROR));

        return $result;
    }

    /** @param resource $process */
    private function retire($process, int $pid): void
    {
        proc_terminate($process);
        if ($pid > 0 && 'Windows' === PHP_OS_FAMILY) {
            $systemRoot = getenv('SystemRoot');
            $taskkill = is_string($systemRoot) ? $systemRoot . '/System32/taskkill.exe' : '';
            if (TemporaryEventWorkspace::ordinary($taskkill)) {
                $pipes = [];
                $killer = proc_open([$taskkill, '/PID', (string) $pid, '/T', '/F'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->owned()->root(), null, ['bypass_shell' => true]);
                if (is_resource($killer)) {
                    foreach ($pipes as $pipe) {
                        fclose($pipe);
                    }
                    proc_close($killer);
                }
            }
        }
        $deadline = hrtime(true) + 30_000_000_000;
        do {
            $status = proc_get_status($process);
            if (!$status['running']) {
                return;
            }
            usleep(10_000);
        } while (hrtime(true) < $deadline);

        throw new RuntimeException('phase22_process_retirement_uncertain');
    }

    private function repositoryFingerprint(): string
    {
        $paths = ['composer.lock', 'skeletons/event-minimal/composer.lock'];
        foreach (self::TEMPLATE_PATHS as $relative) {
            $paths[] = 'skeletons/event-minimal/' . $relative;
        }
        foreach (['api-metadata', 'diagnostics', 'event-contract', 'event-runtime'] as $name) {
            $paths[] = 'packages/' . $name . '/composer.json';
        }
        $parts = [];
        foreach ($paths as $path) {
            $parts[] = $path . "\t" . hash_file('sha256', $this->repositoryRoot . '/' . $path);
        }

        return hash('sha256', implode("\n", $parts));
    }

    private function record(string $name): void
    {
        $this->check(self::GROUPS[count($this->completed)] === $name);
        $this->completed[] = $name;
    }

    private function owned(): TemporaryEventWorkspace
    {
        if (null === $this->workspace) {
            throw new RuntimeException('phase22_workspace_failed');
        }

        return $this->workspace;
    }

    private function check(bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException('phase22_case_failed');
        }
    }

    /** @param Closure(ApplicationEvent): void $onHandle */
    private static function handler(Closure $onHandle): EventHandler
    {
        return new class ($onHandle) implements EventHandler {
            /** @param Closure(ApplicationEvent): void $onHandle */
            public function __construct(private readonly Closure $onHandle) {}

            public function handle(ApplicationEvent $event): void
            {
                ($this->onHandle)($event);
            }
        };
    }
}

class AcceptanceBaseEvent implements ApplicationEvent {}

final class AcceptanceDerivedEvent extends AcceptanceBaseEvent {}
